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
        $mode = strtolower(trim((string)(getenv('EXTPLORER_REMOTE_SECURITY_MODE') ?: self::COMPAT)));
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
