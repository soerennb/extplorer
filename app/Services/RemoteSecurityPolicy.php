<?php

namespace App\Services;

use RuntimeException;

/**
 * Compatibility gate for remote protocols and SSH host identity checks.
 */
final class RemoteSecurityPolicy
{
    public const COMPAT = 'compat';
    public const STRICT = 'strict';

    public function mode(): string
    {
        $mode = strtolower(trim((string)(getenv('EXTPLORER_REMOTE_SECURITY_MODE') ?: self::STRICT)));
        if (!in_array($mode, [self::COMPAT, self::STRICT], true)) {
            throw new RuntimeException('EXTPLORER_REMOTE_SECURITY_MODE must be compat or strict.');
        }
        return $mode;
    }

    public function assertProtocolAllowed(string $type, array $config = []): void
    {
        $type = strtolower($type);
        if ($type === 'ssh2') {
            $type = 'sftp';
        }

        if ($this->mode() !== self::STRICT) {
            if ($type === 'ftp') {
                log_message('warning', 'Plain FTP is enabled in compatibility mode; use FTPS or SFTP.');
            }
            if ($type === 'sftp' && trim((string)($config['host_key_fingerprint'] ?? '')) === '') {
                log_message('warning', 'SFTP host-key verification is disabled in compatibility mode.');
            }
            if ($type === 'ftps') {
                log_message('warning', 'FTPS compatibility mode does not authenticate the credential-bearing connection.');
            }
            return;
        }

        if ($type === 'ftp') {
            throw new RuntimeException(lang('Remote.ftp_strict_denied'));
        }
        if ($type === 'ftps') {
            // PHP ftp_ssl_connect does not verify its peer. A different TLS
            // preflight cannot authenticate the connection carrying credentials.
            throw new RuntimeException(lang('Remote.ftps_strict_denied'));
        }
        if ($type === 'sftp' && $this->normalizeFingerprint((string)($config['host_key_fingerprint'] ?? '')) === '') {
            throw new RuntimeException('SFTP host-key fingerprint is required in strict remote security mode.');
        }
    }

    /** Verify the public host key returned by the negotiated SSH connection. */
    public function assertSshHostKey(string $publicKey, string $expected): void
    {
        $expected = $this->normalizeFingerprint($expected);
        if ($expected === '') {
            if ($this->mode() === self::STRICT) throw new RuntimeException('SFTP host-key fingerprint is required in strict remote security mode.');
            return;
        }
        $parts = explode(' ', $publicKey, 3);
        $raw = isset($parts[1]) ? base64_decode($parts[1], true) : false;
        $algorithm = match (strlen($expected)) { 32 => 'md5', 40 => 'sha1', 64 => 'sha256', default => null };
        if ($raw === false || $algorithm === null || !hash_equals($expected, hash($algorithm, $raw))) throw new RuntimeException('SFTP host-key fingerprint verification failed.');
    }

    /**
     * Verify the server certificate before the FTP control connection is
     * opened. PHP's FTP extension does not expose peer verification options,
     * so the TLS preflight is performed against the resolver-pinned address.
     *
     * @param array{cafile?: string, spki_pin?: string} $config
     */
    public function verifyFtpsCertificate(string $host, string $connectHost, int $port, array $config = []): void
    {
        if (!function_exists('stream_socket_client') || !function_exists('openssl_pkey_get_public')) {
            throw new RuntimeException('FTPS certificate verification is not available on this server.');
        }

        $cafile = trim((string) ($config['cafile'] ?? getenv('EXTPLORER_FTPS_CA_FILE') ?: ''));
        if ($cafile !== '' && (!is_file($cafile) || !is_readable($cafile))) {
            throw new RuntimeException('Configured FTPS CA file is not readable.');
        }

        $context = stream_context_create([
            'ssl' => array_filter([
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'peer_name' => $host,
                'cafile' => $cafile !== '' ? $cafile : null,
                'capture_peer_cert' => true,
                'disable_compression' => true,
            ], static fn ($value): bool => $value !== null),
        ]);

        $timeout = (new ResourcePolicy())->remoteTimeoutSeconds();
        $socket = @stream_socket_client(
            sprintf('tcp://%s:%d', $this->formatAddress($connectHost), $port),
            $errorCode,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!is_resource($socket)) {
            throw new RuntimeException('FTPS certificate verification failed.');
        }

        try {
            stream_set_timeout($socket, $timeout);
            $this->expectFtpReply($socket, 220);
            if (fwrite($socket, "AUTH TLS\r\n") !== 10) throw new RuntimeException('FTPS TLS negotiation failed.');
            $this->expectFtpReply($socket, 234);
            if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) throw new RuntimeException('FTPS certificate verification failed.');
            $parameters = stream_context_get_params($socket);
            $certificate = $parameters['options']['ssl']['peer_certificate'] ?? null;
            if (!is_object($certificate)) {
                throw new RuntimeException('FTPS certificate verification failed.');
            }

            $pin = $this->normalizePin((string) ($config['spki_pin'] ?? ''));
            if ($pin !== '') {
                $actual = $this->spkiFingerprint($certificate);
                if ($actual === '' || !hash_equals($pin, $actual)) {
                    throw new RuntimeException('FTPS certificate pin verification failed.');
                }
            }
        } finally {
            fclose($socket);
        }
    }

    /** Read a bounded FTP reply, including a multiline server greeting. */
    private function expectFtpReply($socket, int $expected): void
    {
        $bytes = 0;
        for ($lines = 0; $lines < 100; $lines++) {
            $line = fgets($socket, 4097);
            if ($line === false || ($bytes += strlen($line)) > 16384) break;
            if (preg_match('/\A([0-9]{3}) /', $line, $match) === 1) {
                if ((int)$match[1] === $expected) return;
                break;
            }
        }
        throw new RuntimeException('FTPS TLS negotiation failed.');
    }

    private function normalizePin(string $pin): string
    {
        $pin = trim($pin);
        if (str_starts_with(strtolower($pin), 'sha256/')) {
            $pin = substr($pin, 7);
            $decoded = base64_decode($pin, true);
            return is_string($decoded) && strlen($decoded) === 32 ? bin2hex($decoded) : '';
        }
        if (str_starts_with(strtolower($pin), 'sha256:')) {
            $pin = substr($pin, 7);
        }

        $hex = preg_replace('/[^a-f0-9]/i', '', $pin) ?? '';
        if (strlen($hex) === 64 && strlen($hex) === strlen(preg_replace('/[:\s-]/', '', $pin))) {
            return strtolower($hex);
        }

        $decoded = base64_decode($pin, true);
        return is_string($decoded) && strlen($decoded) === 32 ? bin2hex($decoded) : '';
    }

    public function normalizeTlsSpkiPin(string $pin): string
    {
        return $this->normalizePin($pin);
    }

    private function spkiFingerprint(mixed $certificate): string
    {
        $publicKey = openssl_pkey_get_public($certificate);
        if ($publicKey === false) {
            return '';
        }
        $details = openssl_pkey_get_details($publicKey);
        $pem = is_array($details) ? ($details['key'] ?? '') : '';
        if (!is_string($pem) || $pem === '') {
            return '';
        }
        $der = base64_decode((string)(preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', $pem) ?? ''), true);
        return is_string($der) && $der !== '' ? hash('sha256', $der) : '';
    }

    private function formatAddress(string $address): string
    {
        return str_contains($address, ':') && !str_starts_with($address, '[')
            ? '[' . $address . ']'
            : $address;
    }

    public function normalizeFingerprint(string $fingerprint): string
    {
        $fingerprint = trim($fingerprint);
        $fingerprint = preg_replace('/\A(?:sha256|sha1|md5):/i', '', $fingerprint) ?? '';
        $hex = preg_replace('/[:\s-]/', '', $fingerprint) ?? '';
        if ($hex !== '' && ctype_xdigit($hex)) return strtolower($hex);
        // Base64 is case-sensitive. Normalize a SHA-256 digest to canonical hex.
        $raw = base64_decode($fingerprint, true);
        return is_string($raw) && strlen($raw) === 32 ? bin2hex($raw) : '';
    }
}
