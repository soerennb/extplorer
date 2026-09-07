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
            return;
        }

        if ($type === 'ftp') {
            throw new RuntimeException('Plain FTP is disabled in strict remote security mode. Use FTPS or SFTP.');
        }
        if ($type === 'ftps' && empty($config['tls_verified'])) {
            throw new RuntimeException('FTPS certificate verification is required in strict remote security mode.');
        }
        if ($type === 'sftp' && $this->normalizeFingerprint((string)($config['host_key_fingerprint'] ?? '')) === '') {
            throw new RuntimeException('SFTP host-key fingerprint is required in strict remote security mode.');
        }
    }

    public function assertSshFingerprint(object $connection, string $expected): void
    {
        $expected = $this->normalizeFingerprint($expected);
        if ($expected === '') {
            if ($this->mode() === self::STRICT) {
                throw new RuntimeException('SFTP host-key fingerprint is required in strict remote security mode.');
            }
            return;
        }

        if (!function_exists('ssh2_fingerprint')) {
            throw new RuntimeException('SSH2 host-key verification is not available on this server.');
        }

        $flags = 0;
        if (defined('SSH2_FINGERPRINT_SHA256')) {
            $flags |= SSH2_FINGERPRINT_SHA256;
        }
        if (defined('SSH2_FINGERPRINT_HEX')) {
            $flags |= SSH2_FINGERPRINT_HEX;
        }
        $actual = ssh2_fingerprint($connection, $flags);
        if (!is_string($actual) || !hash_equals($expected, $this->normalizeFingerprint($actual))) {
            throw new RuntimeException('SFTP host-key fingerprint verification failed.');
        }
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
            sprintf('tls://%s:%d', $this->formatAddress($connectHost), $port),
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
        if (str_starts_with(strtolower($fingerprint), 'sha256:')) {
            $fingerprint = substr($fingerprint, 7);
        }
        $hex = preg_replace('/[^a-f0-9]/i', '', $fingerprint) ?? '';
        if ($hex !== '' && preg_match('/\A[a-f0-9]+\z/i', $hex) === 1 && strlen($hex) === strlen(preg_replace('/[:\s-]/', '', $fingerprint))) {
            return strtolower($hex);
        }

        // SHA-256 fingerprints are also commonly represented as base64.
        return strtolower(preg_replace('/\s+/', '', $fingerprint) ?? '');
    }
}
