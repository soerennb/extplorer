<?php

namespace App\Controllers;

use App\Services\SettingsService;
use App\Services\EmailService;
use App\Services\LogService;
use App\Services\RemoteEndpointPolicy;
use App\Services\StepUpAuthenticationService;

class SettingsController extends BaseController
{
    use ApiResponseTrait;

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

    private function requireStepUp(string $action)
    {
        if ((new StepUpAuthenticationService())->consume($this->request, $action)) {
            return true;
        }

        return $this->fail([
            'error' => 'Additional authentication is required.',
            'action' => $action,
        ], 428, 'step_up_required');
    }

    public function index()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $settings = $this->settingsService->getSettings();
        $emailService = new EmailService();
        $settings['email_configured'] = $emailService->isConfigured($settings);
        $deliveryStatus = $emailService->deliveryStatus($settings);
        $settings['email_delivery_ready'] = $deliveryStatus['ready'];
        $settings['email_delivery_reason'] = $deliveryStatus['reason'];
        $settings['email_delivery_verified_at'] = $deliveryStatus['verified_at'];
        unset($settings['email_delivery_verified_fingerprint']);
        
        // Mask password
        if (!empty($settings['smtp_pass'])) {
            $settings['smtp_pass'] = '********';
        }

        $settings['mount_root_allowlist_text'] = implode("\n", $settings['mount_root_allowlist'] ?? []);
        $remoteEndpoints = (new RemoteEndpointPolicy())->allowlistedEndpoints();
        $settings['remote_endpoint_allowlist'] = $remoteEndpoints;
        $settings['remote_endpoint_allowlist_text'] = implode("\n", array_map(
            static fn(array $endpoint): string => sprintf('%s://%s:%d', $endpoint['protocol'], $endpoint['host'], $endpoint['port']),
            $remoteEndpoints
        ));
        $settings['share_upload_allowed_extensions_text'] = implode("\n", $settings['share_upload_allowed_extensions'] ?? []);

        return $this->respond($settings);
    }

    public function update()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        if (($stepUp = $this->requireStepUp('settings.update')) !== true) return $stepUp;

        $json = $this->request->getJSON(true);
        if (!$json) return $this->fail('Invalid JSON');

        $currentSettings = $this->settingsService->getSettings();

        // Derived flags should not be persisted.
        unset(
            $json['email_configured'],
            $json['email_delivery_ready'],
            $json['email_delivery_reason'],
            $json['email_delivery_verified_at'],
            $json['email_delivery_verified_fingerprint']
        );

        if (isset($json['mount_root_allowlist_text'])) {
            $json['mount_root_allowlist'] = $this->parseTextList($json['mount_root_allowlist_text']);
            unset($json['mount_root_allowlist_text']);
        }

        if (isset($json['mount_root_allowlist']) && is_string($json['mount_root_allowlist'])) {
            $json['mount_root_allowlist'] = $this->parseTextList($json['mount_root_allowlist']);
        }

        try {
            if (array_key_exists('remote_endpoint_allowlist_text', $json)) {
                $json['remote_endpoint_allowlist'] = $this->parseRemoteEndpointList($json['remote_endpoint_allowlist_text']);
                unset($json['remote_endpoint_allowlist_text']);
            } elseif (array_key_exists('remote_endpoint_allowlist', $json)) {
                if (is_string($json['remote_endpoint_allowlist']) || is_array($json['remote_endpoint_allowlist'])) {
                    $json['remote_endpoint_allowlist'] = $this->parseRemoteEndpointList($json['remote_endpoint_allowlist']);
                } else {
                    return $this->fail(
                        'Invalid remote endpoint allowlist entry. Use protocol://server:port.',
                        422,
                        'invalid_remote_endpoint_allowlist'
                    );
                }
            }

            if (array_key_exists('remote_login_enabled', $json)) {
                $json['remote_login_enabled'] = filter_var($json['remote_login_enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($json['remote_login_enabled'] === null) {
                    return $this->fail('Invalid remote login setting');
                }
            }
        } catch (\InvalidArgumentException) {
            return $this->fail(
                'Invalid remote endpoint allowlist entry. Use protocol://server:port.',
                422,
                'invalid_remote_endpoint_allowlist'
            );
        }

        if (array_key_exists('remote_login_enabled', $json) && !is_bool($json['remote_login_enabled'])) {
            return $this->fail('Invalid remote login setting');
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

        if (array_key_exists('webdav_enabled', $json)) {
            $json['webdav_enabled'] = (bool)$json['webdav_enabled'];
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

        $savedSettings = $this->settingsService->getSettings();
        $deliveryStatus = (new EmailService())->deliveryStatus($savedSettings);

        return $this->respond([
            'status' => 'success',
            'email_delivery_ready' => $deliveryStatus['ready'],
            'email_delivery_verified_at' => $deliveryStatus['verified_at'],
        ]);
    }

    public function testEmail()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $payload = $this->request->getJSON(true) ?? [];
        $email = $payload['email'] ?? null;
        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('A valid recipient email is required.');
        }

        $current = $this->settingsService->getSettings();
        if (($payload['smtp_pass'] ?? null) === '********') {
            $payload['smtp_pass'] = $current['smtp_pass'] ?? '';
        }
        $settings = array_merge($current, $payload);

        $svc = new EmailService();
        if (!hash_equals($svc->configFingerprint($current), $svc->configFingerprint($settings))) {
            return $this->fail('Save the email settings before sending a test email.');
        }
        $result = $svc->sendTestEmailWithConfig($email, $settings);
        if ($result['ok']) {
            $verifiedAt = $svc->markDeliveryVerified($settings);
            return $this->respond([
                'status' => 'success',
                'email_delivery_ready' => true,
                'email_delivery_verified_at' => $verifiedAt,
            ]);
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

            $endpoint = is_array($entry)
                ? $policy->parseEndpoint(sprintf(
                    '%s://%s:%d',
                    (string)($entry['protocol'] ?? ''),
                    (string)($entry['host'] ?? ''),
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
