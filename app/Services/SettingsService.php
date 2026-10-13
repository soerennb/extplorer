<?php

namespace App\Services;

class SettingsService
{
    private string $settingsFile;

    public function __construct()
    {
        $this->settingsFile = config('Storage')->state . '/settings.php';
    }

    /**
     * Reads settings from the PHP-wrapped JSON file.
     */
    public function getSettings(): array
    {
        if (!AtomicFileStore::exists($this->settingsFile)) {
            return $this->getDefaultSettings();
        }

        $defaults = $this->getDefaultSettings();
        $settings = array_merge($defaults, AtomicFileStore::read($this->settingsFile));
        foreach ($defaults as $key => $default) {
            $value = $settings[$key];
            $settings[$key] = match (true) {
                is_int($default) => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default,
                is_bool($default) => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default,
                is_string($default) => is_string($value) ? $value : $default,
                is_array($default) => is_array($value) ? $value : $default,
                default => $value,
            };
        }
        return $settings;
    }

    /**
     * Saves settings to the file with PHP protection.
     */
    public function saveSettings(array $settings): void
    {
        AtomicFileStore::transaction($this->settingsFile, function (array &$current) use ($settings): void {
            $current = array_merge($this->getDefaultSettings(), $current, $settings);
        }, $this->getDefaultSettings());
    }

    public function get(string $key, $default = null)
    {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }

    private function getDefaultSettings(): array
    {
        return [
            'email_protocol' => 'smtp',
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_crypto' => 'tls', // ssl, tls, null
            'sendmail_path' => '/usr/sbin/sendmail',
            'email_from' => 'noreply@example.com',
            'email_from_name' => 'eXtplorer',
            'email_delivery_verified_fingerprint' => '',
            'email_delivery_verified_at' => null,
            'default_transfer_expiry' => 7,
            'transfer_max_expiry_days' => 30,
            'transfer_default_notify_download' => false,
            'log_retention_count' => 2000,
            'session_idle_timeout_minutes' => 120,
            'share_default_expiry_days' => 7,
            'share_require_expiry' => false,
            'share_max_expiry_days' => 30,
            'share_require_password' => false,
            'upload_max_file_mb' => 100,
            'share_upload_allowed_extensions' => [],
            'share_upload_quota_mb' => 0,
            'share_upload_max_files' => 0,
            'quota_per_user_mb' => 0,
            'allow_public_uploads' => false,
            'webdav_enabled' => true,
            'mount_root_allowlist' => [],
            'mount_remote_host_allowlist' => [],
            'remote_login_enabled' => false,
            'remote_endpoint_allowlist' => [],
        ];
    }
}
