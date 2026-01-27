<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Services\SettingsService;
use App\Services\EmailService;
use App\Services\LogService;

class SettingsController extends BaseController
{
    use ResponseTrait;

    private SettingsService $settingsService;

    public function __construct()
    {
        $this->settingsService = new SettingsService();
    }

    private function checkAdmin()
    {
        // Allow either the dedicated settings permission or the legacy admin_users permission.
        if (!can('admin_settings') && !can('admin_users')) {
            return $this->failForbidden('Access denied');
        }
        return true;
    }

    public function index()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $settings = $this->settingsService->getSettings();
        $emailService = new EmailService();
        $settings['email_configured'] = $emailService->isConfigured($settings);
        
        // Mask password
        if (!empty($settings['smtp_pass'])) {
            $settings['smtp_pass'] = '********';
        }

        $settings['mount_root_allowlist_text'] = implode("\n", $settings['mount_root_allowlist'] ?? []);
        $settings['share_upload_allowed_extensions_text'] = implode("\n", $settings['share_upload_allowed_extensions'] ?? []);

        return $this->respond($settings);
    }

    public function update()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $json = $this->request->getJSON(true);
        if (!$json) return $this->fail('Invalid JSON');

        $currentSettings = $this->settingsService->getSettings();

        // Derived flags should not be persisted.
        unset($json['email_configured']);

        if (isset($json['mount_root_allowlist_text'])) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $json['mount_root_allowlist_text']);
            $json['mount_root_allowlist'] = array_values(array_filter(array_map('trim', $lines)));
            unset($json['mount_root_allowlist_text']);
        }

        if (isset($json['mount_root_allowlist']) && is_string($json['mount_root_allowlist'])) {
            $lines = preg_split('/\r\n|\r|\n/', $json['mount_root_allowlist']);
            $json['mount_root_allowlist'] = array_values(array_filter(array_map('trim', $lines)));
        }

        if (isset($json['share_upload_allowed_extensions_text'])) {
            $json['share_upload_allowed_extensions'] = $this->parseExtensions($json['share_upload_allowed_extensions_text']);
            unset($json['share_upload_allowed_extensions_text']);
        }

        if (isset($json['share_upload_allowed_extensions']) && is_string($json['share_upload_allowed_extensions'])) {
            $json['share_upload_allowed_extensions'] = $this->parseExtensions($json['share_upload_allowed_extensions']);
        }

        if (isset($json['email_protocol'])) {
            $protocol = strtolower(trim((string) $json['email_protocol']));
            $allowed = ['smtp', 'sendmail', 'mail'];
            if (!in_array($protocol, $allowed, true)) {
                return $this->fail('Invalid email protocol');
            }
            $json['email_protocol'] = $protocol;
        }

        if (isset($json['sendmail_path'])) {
            $path = trim((string) $json['sendmail_path']);
            if ($path === '' || strpos($path, "\0") !== false) {
                return $this->fail('Invalid sendmail path');
            }
            $json['sendmail_path'] = $path;
        }

        if (isset($json['log_retention_count'])) {
            $retention = (int)$json['log_retention_count'];
            if ($retention < 100 || $retention > 20000) {
                return $this->fail('Log retention count must be between 100 and 20000');
            }
            $json['log_retention_count'] = $retention;
        }

        if (isset($json['transfer_max_expiry_days'])) {
            $maxExpiry = (int)$json['transfer_max_expiry_days'];
            if ($maxExpiry < 1 || $maxExpiry > 365) {
                return $this->fail('Transfer max expiry must be between 1 and 365 days');
            }
            $json['transfer_max_expiry_days'] = $maxExpiry;
        }

        if (isset($json['default_transfer_expiry'])) {
            $defaultExpiry = (int)$json['default_transfer_expiry'];
            if ($defaultExpiry < 1) {
                return $this->fail('Default transfer expiry must be at least 1 day');
            }

            $maxExpiry = (int)($json['transfer_max_expiry_days'] ?? $currentSettings['transfer_max_expiry_days'] ?? 30);
            if ($defaultExpiry > $maxExpiry) {
                return $this->fail('Default transfer expiry cannot exceed the transfer max expiry');
            }

            $json['default_transfer_expiry'] = $defaultExpiry;
        }

        if (array_key_exists('transfer_default_notify_download', $json)) {
            $json['transfer_default_notify_download'] = (bool)$json['transfer_default_notify_download'];
        }

        if (isset($json['session_idle_timeout_minutes'])) {
            $minutes = (int)$json['session_idle_timeout_minutes'];
            if ($minutes < 0 || $minutes > 1440) {
                return $this->fail('Session idle timeout must be between 0 and 1440 minutes');
            }
            $json['session_idle_timeout_minutes'] = $minutes;
        }

        if (array_key_exists('share_require_expiry', $json)) {
            $json['share_require_expiry'] = (bool)$json['share_require_expiry'];
        }

        if (array_key_exists('share_require_password', $json)) {
            $json['share_require_password'] = (bool)$json['share_require_password'];
        }

        if (isset($json['share_max_expiry_days'])) {
            $maxShareExpiry = (int)$json['share_max_expiry_days'];
            if ($maxShareExpiry < 1 || $maxShareExpiry > 365) {
                return $this->fail('Share max expiry must be between 1 and 365 days');
            }
            $json['share_max_expiry_days'] = $maxShareExpiry;
        }

        if (isset($json['share_default_expiry_days'])) {
            $defaultShareExpiry = (int)$json['share_default_expiry_days'];
            if ($defaultShareExpiry < 1) {
                return $this->fail('Share default expiry must be at least 1 day');
            }
            $maxShareExpiry = (int)($json['share_max_expiry_days'] ?? $currentSettings['share_max_expiry_days'] ?? 30);
            if ($defaultShareExpiry > $maxShareExpiry) {
                return $this->fail('Share default expiry cannot exceed the share max expiry');
            }
            $json['share_default_expiry_days'] = $defaultShareExpiry;
        }

        if (array_key_exists('allow_public_uploads', $json)) {
            $json['allow_public_uploads'] = (bool)$json['allow_public_uploads'];
        }

        if (isset($json['share_upload_quota_mb'])) {
            $quotaMb = (int)$json['share_upload_quota_mb'];
            if ($quotaMb < 0 || $quotaMb > 1024000) {
                return $this->fail('Share upload quota must be between 0 and 1024000 MB');
            }
            $json['share_upload_quota_mb'] = $quotaMb;
        }

        if (isset($json['share_upload_max_files'])) {
            $maxFiles = (int)$json['share_upload_max_files'];
            if ($maxFiles < 0 || $maxFiles > 100000) {
                return $this->fail('Share upload max files must be between 0 and 100000');
            }
            $json['share_upload_max_files'] = $maxFiles;
        }

        if (isset($json['share_upload_allowed_extensions']) && is_array($json['share_upload_allowed_extensions'])) {
            $json['share_upload_allowed_extensions'] = $this->parseExtensions($json['share_upload_allowed_extensions']);
        }

        if (isset($json['upload_max_file_mb'])) {
            $maxFileMb = (int)$json['upload_max_file_mb'];
            if ($maxFileMb < 0 || $maxFileMb > 10240) {
                return $this->fail('Upload max file size must be between 0 and 10240 MB');
            }
            $json['upload_max_file_mb'] = $maxFileMb;
        }

        if (isset($json['quota_per_user_mb'])) {
            $quotaMb = (int)$json['quota_per_user_mb'];
            if ($quotaMb < 0 || $quotaMb > 102400) {
                return $this->fail('Per-user quota must be between 0 and 102400 MB');
            }
            $json['quota_per_user_mb'] = $quotaMb;
        }

        // If password is mask, don't update it (keep existing)
        if (isset($json['smtp_pass']) && $json['smtp_pass'] === '********') {
            unset($json['smtp_pass']);
        }

        $this->settingsService->saveSettings($json);
        LogService::log('Update Settings', 'System Settings Updated');
        
        return $this->respond(['status' => 'success']);
    }

    public function testEmail()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $payload = $this->request->getJSON(true) ?? [];
        $email = $payload['email'] ?? null;
        if (!$email) return $this->fail('Email required');

        $current = $this->settingsService->getSettings();
        if (($payload['smtp_pass'] ?? null) === '********') {
            $payload['smtp_pass'] = $current['smtp_pass'] ?? '';
        }
        $settings = array_merge($current, $payload);

        $svc = new EmailService();
        $result = $svc->sendTestEmailWithConfig($email, $settings);
        if ($result['ok']) {
            return $this->respond(['status' => 'success']);
        }

        return $this->fail('Failed to send email. ' . strip_tags($result['debug'] ?? ''));
    }

    public function validateEmail()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $payload = $this->request->getJSON(true) ?? [];
        $current = $this->settingsService->getSettings();
        if (($payload['smtp_pass'] ?? null) === '********') {
            $payload['smtp_pass'] = $current['smtp_pass'] ?? '';
        }
        $settings = array_merge($current, $payload);

        $svc = new EmailService();
        $result = $svc->validateConfig($settings);
        if ($result['ok']) {
            return $this->respond(['status' => 'success', 'message' => $result['message'] ?? 'OK']);
        }

        return $this->fail($result['message'] ?? 'Validation failed');
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
}
