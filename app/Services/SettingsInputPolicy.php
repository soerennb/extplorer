<?php

namespace App\Services;

/** Validates the settings boundary without mixing HTTP responses into field policy. */
final class SettingsInputPolicy
{
    private const INTEGER_FIELDS = [
        'log_retention_count' => [100, 20000, 'Log retention count must be between 100 and 20000'],
        'transfer_max_expiry_days' => [1, 365, 'Transfer max expiry must be between 1 and 365 days'],
        'session_idle_timeout_minutes' => [0, 1440, 'Session idle timeout must be between 0 and 1440 minutes'],
        'share_max_expiry_days' => [1, 365, 'Share max expiry must be between 1 and 365 days'],
        'share_upload_quota_mb' => [0, 1024000, 'Share upload quota must be between 0 and 1024000 MB'],
        'share_upload_max_files' => [0, 100000, 'Share upload max files must be between 0 and 100000'],
        'upload_max_file_mb' => [0, 10240, 'Upload max file size must be between 0 and 10240 MB'],
        'quota_per_user_mb' => [0, 102400, 'Per-user quota must be between 0 and 102400 MB'],
    ];
    private const BOOLEAN_FIELDS = ['remote_login_enabled', 'transfer_default_notify_download', 'share_require_expiry',
        'share_require_password', 'allow_public_uploads', 'webdav_enabled'];

    public function normalize(array $input, array $current): array
    {
        if (array_key_exists('sendmail_path', $input)) {
            throw new SettingsValidationException(lang('Mail.sendmail_path_read_only'), 422, 'sendmail_path_read_only');
        }
        foreach (['email_configured', 'email_delivery_ready', 'email_delivery_reason', 'email_delivery_verified_at', 'email_delivery_verified_fingerprint'] as $key) unset($input[$key]);
        foreach (['mount_root_allowlist', 'share_upload_allowed_extensions'] as $key) {
            if (isset($input[$key . '_text'])) {
                $input[$key] = $input[$key . '_text'];
                unset($input[$key . '_text']);
            }
            if (isset($input[$key])) $input[$key] = $key === 'mount_root_allowlist'
                ? (is_array($input[$key]) ? $input[$key] : $this->parseTextList($input[$key]))
                : $this->parseExtensions($input[$key]);
        }
        $remoteKey = array_key_exists('remote_endpoint_allowlist_text', $input) ? 'remote_endpoint_allowlist_text' : 'remote_endpoint_allowlist';
        if (array_key_exists($remoteKey, $input)) {
            try {
                if (!is_string($input[$remoteKey]) && !is_array($input[$remoteKey])) throw new \InvalidArgumentException();
                $input['remote_endpoint_allowlist'] = $this->parseRemoteEndpointList($input[$remoteKey]);
                unset($input['remote_endpoint_allowlist_text']);
            } catch (\InvalidArgumentException) {
                throw new SettingsValidationException('Invalid remote endpoint allowlist entry. Use protocol://server:port.', 422, 'invalid_remote_endpoint_allowlist');
            }
        }
        foreach (self::BOOLEAN_FIELDS as $key) {
            if (!array_key_exists($key, $input)) continue;
            $value = filter_var($input[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value === null) throw new SettingsValidationException($key === 'remote_login_enabled' ? 'Invalid remote login setting' : 'Invalid boolean setting: ' . $key);
            $input[$key] = $value;
        }
        foreach (self::INTEGER_FIELDS as $key => [$min, $max, $message]) {
            if (!isset($input[$key])) continue;
            $value = filter_var($input[$key], FILTER_VALIDATE_INT);
            if ($value === false || $value < $min || $value > $max) throw new SettingsValidationException($message);
            $input[$key] = $value;
        }
        foreach (['default_transfer_expiry' => ['transfer_max_expiry_days', 'Default transfer'], 'share_default_expiry_days' => ['share_max_expiry_days', 'Share default']] as $key => [$maxKey, $label]) {
            $merged = array_merge($current, $input);
            $value = filter_var($merged[$key] ?? 7, FILTER_VALIDATE_INT);
            if ($value === false || $value < 1) throw new SettingsValidationException($label . ' expiry must be at least 1 day');
            if ($value > (int)($merged[$maxKey] ?? 30)) throw new SettingsValidationException($label . ' expiry cannot exceed the ' . ($key === 'default_transfer_expiry' ? 'transfer' : 'share') . ' max expiry');
            if (isset($input[$key])) $input[$key] = $value;
        }
        if (($input['smtp_pass'] ?? '') === '********') unset($input['smtp_pass']);
        $mailKeys = array_flip(MailConfigPolicy::FIELDS);
        if (array_intersect_key($input, $mailKeys) !== []) {
            try {
                $canonical = (new MailConfigPolicy())->normalize(array_replace($current, $input));
                if (array_key_exists('smtp_host', $input)) (new MailEndpointPolicy())->assertLiteralAllowed($canonical['smtp_host']);
                $input = array_replace($input, array_intersect_key($canonical, $input));
            } catch (MailConfigurationException $error) {
                throw new SettingsValidationException(lang('Mail.' . $error->reason), 422, $error->reason);
            }
        }
        return $input;
    }

    /**
     * Parse and normalize an extension list from settings input.
     *
     * @param mixed $raw
     * @return array<int, string>
     */
    private function parseExtensions($raw): array
    {
        $extensions = [];

        if (is_string($raw)) {
            $extensions = preg_split('/[\s,;]+/', $raw) ?: [];
        } elseif (is_array($raw)) {
            $extensions = $raw;
        }

        $normalized = [];
        foreach ($extensions as $ext) {
            if (!is_string($ext)) throw new SettingsValidationException('Invalid upload extension list.');
            $ext = strtolower(trim((string)$ext));
            $ext = ltrim($ext, '.');
            if ($ext === '') {
                continue;
            }

            // Keep extensions reasonably strict and predictable.
            if (!preg_match('/^[a-z0-9]+$/', $ext)) {
                continue;
            }

            $normalized[] = $ext;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    /**
     * Parse newline separated lists from settings input.
     *
     * @param mixed $raw
     * @return array<int, string>
     */
    private function parseTextList($raw): array
    {
        if (!is_string($raw)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        return array_values(array_filter(array_map('trim', $lines)));
    }

    /**
     * Parse exact protocol://host:port remote endpoints. Invalid entries are
     * rejected instead of silently broadening the outbound access policy.
     *
     * @param mixed $raw
     * @return list<array{protocol: string, host: string, port: int}>
     */
    private function parseRemoteEndpointList($raw): array
    {
        $entries = is_string($raw)
            ? (preg_split('/\r\n|\r|\n/', $raw) ?: [])
            : (is_array($raw) ? $raw : []);
        $policy = new RemoteEndpointPolicy();
        $normalized = [];

        foreach ($entries as $entry) {
            if (is_string($entry) && trim($entry) === '') {
                continue;
            }

            if (!is_string($entry) && !is_array($entry)) throw new \InvalidArgumentException();
            if (is_array($entry) && (!is_string($entry['protocol'] ?? null) || !is_string($entry['host'] ?? null) || filter_var($entry['port'] ?? null, FILTER_VALIDATE_INT) === false)) throw new \InvalidArgumentException();
            $endpoint = is_array($entry)
                ? $policy->parseEndpoint(sprintf(
                    '%s://%s:%d',
                    $entry['protocol'],
                    $entry['host'],
                    (int)($entry['port'] ?? 0)
                ))
                : $policy->parseEndpoint((string)$entry);
            if ($endpoint === null) {
                throw new \InvalidArgumentException('Invalid remote endpoint allowlist entry. Use protocol://host:port.');
            }
            $normalized[json_encode($endpoint, JSON_THROW_ON_ERROR)] = $endpoint;
        }

        return array_values($normalized);
    }
}
