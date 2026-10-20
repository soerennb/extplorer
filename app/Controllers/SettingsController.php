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
        if (!can('admin_settings')) {
            LogService::event('Settings Access', 'denied');
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
        $settings['sendmail_path'] = (new \App\Services\SendmailPolicy())->configuredPath();
        
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

        try {
            $json = (new \App\Services\SettingsInputPolicy())->normalize($json, $currentSettings);
        } catch (\App\Services\SettingsValidationException $error) {
            return $this->fail($error->getMessage(), $error->getCode(), $error->errorKey);
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
        if (($check = $this->requireStepUp('settings.email.test')) !== true) return $check;
        if (($check = $this->checkMailProbeLimit()) !== true) return $check;

        $payload = $this->request->getJSON(true) ?? [];
        if (!is_array($payload) || array_diff(array_keys($payload), ['email']) !== []) {
            return $this->fail(lang('Mail.email_saved_settings_only'), 422, 'email_saved_settings_only');
        }
        $email = $payload['email'] ?? null;
        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail(lang('Mail.email_recipient_required'), 422, 'email_recipient_required');
        }

        $settings = $this->settingsService->getSettings();
        $svc = new EmailService();
        $result = $svc->sendTestEmailWithConfig($email, $settings);
        LogService::event('Mail Test', $result['ok'] ? 'success' : 'failed', ['reason' => $result['error']]);
        if ($result['ok']) {
            $verifiedAt = $svc->markDeliveryVerified($settings);
            return $this->respond(['status' => 'success', 'email_delivery_ready' => true,
                'email_delivery_verified_at' => $verifiedAt]);
        }
        return $this->fail(lang('Mail.' . $result['error']), 422, $result['error']);
    }

    public function validateEmail()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        if (($check = $this->requireStepUp('settings.email.validate')) !== true) return $check;
        if (($check = $this->checkMailProbeLimit()) !== true) return $check;
        $payload = $this->request->getJSON(true) ?? [];
        if ($payload !== []) {
            return $this->fail(lang('Mail.email_saved_settings_only'), 422, 'email_saved_settings_only');
        }
        $result = (new EmailService())->validateConfig($this->settingsService->getSettings());
        LogService::event('Mail Validation', $result['ok'] ? 'success' : 'failed', ['reason' => $result['error'] ?? null]);
        if ($result['ok']) return $this->respond(['status' => 'success']);
        return $this->fail(lang('Mail.' . $result['error']), 422, $result['error']);
    }

    private function checkMailProbeLimit()
    {
        // One account budget for both probes, independent of client IP changes.
        $key = 'mail-probe-' . hash('sha256', (string)session('username'));
        if (\Config\Services::throttler()->check($key, 5, MINUTE)) return true;
        LogService::event('Mail Probe', 'throttled');
        return $this->fail(lang('Mail.email_probe_rate_limited'), 429, 'email_probe_rate_limited');
    }
}
