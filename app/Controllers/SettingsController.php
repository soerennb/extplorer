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
        if (!can('admin_users')) { // Re-using user admin permission for now
            return $this->failForbidden('Access denied');
        }
        return true;
    }

    public function index()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $settings = $this->settingsService->getSettings();
        
        // Mask password
        if (!empty($settings['smtp_pass'])) {
            $settings['smtp_pass'] = '********';
        }

        $settings['mount_root_allowlist_text'] = implode("\n", $settings['mount_root_allowlist'] ?? []);

        return $this->respond($settings);
    }

    public function update()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $json = $this->request->getJSON(true);
        if (!$json) return $this->fail('Invalid JSON');

        if (isset($json['mount_root_allowlist_text'])) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $json['mount_root_allowlist_text']);
            $json['mount_root_allowlist'] = array_values(array_filter(array_map('trim', $lines)));
            unset($json['mount_root_allowlist_text']);
        }

        if (isset($json['mount_root_allowlist']) && is_string($json['mount_root_allowlist'])) {
            $lines = preg_split('/\r\n|\r|\n/', $json['mount_root_allowlist']);
            $json['mount_root_allowlist'] = array_values(array_filter(array_map('trim', $lines)));
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
}
