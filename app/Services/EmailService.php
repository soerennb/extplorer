<?php

namespace App\Services;

use Config\Services;

class EmailService
{
    private $email;
    private SettingsService $settingsService;
    private ?string $lastError = null;

    public function __construct()
    {
        $this->email = Services::email();
        $this->settingsService = new SettingsService();
    }

    private function prepare(array $settings): bool
    {
        $this->lastError = null;
        try {
            $config = (new MailConfigPolicy())->deliveryConfig($settings);
        } catch (MailConfigurationException $error) {
            log_message('warning', 'Mail operation denied: {reason}', ['reason' => $error->reason]);
            $this->lastError = $error->reason;
            return false;
        }
        $this->email->initialize($config);
        $this->email->setFrom($config['fromEmail'], $config['fromName']);
        return true;
    }

    public function sendTestEmail(string $recipient): bool
    {
        if (!$this->prepare($this->settingsService->getSettings())) {
            log_message('warning', 'Email send skipped: email settings are not configured.');
            return false;
        }

        $this->email->setTo($recipient);
        $this->email->setSubject('eXtplorer Test Email');
        $this->email->setMessage('<h1>Test Email</h1><p>If you are reading this, your email settings are correct.</p>');

        if ($this->email->send()) {
            return true;
        } else {
            // Log error
            log_message('error', 'Test email delivery failed.');
            return false;
        }
    }

    public function sendTransferNotification(array $transfer, string $link): bool
    {
        $settings = $this->settingsService->getSettings();
        if (!$this->isDeliveryReady($settings) || !$this->prepare($settings)) {
            log_message('warning', 'Transfer email skipped: email delivery is not ready.');
            return false;
        }

        $sender = filter_var($transfer['sender_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: 'Unknown';
        $message = (string)($transfer['message'] ?? '');
        $subject = preg_replace('/[\r\n]+/', ' ', (string)($transfer['subject'] ?? 'Files shared with you')) ?: 'Files shared with you';
        $subject = mb_substr(trim($subject), 0, 200);
        $safeSender = esc($sender);
        $safeLink = esc($link);
        
        // Simple HTML Template
        $body = "
            <h2>You received files!</h2>
            <p><strong>{$safeSender}</strong> sent you files via eXtplorer.</p>
            <p><strong>Message:</strong><br>" . nl2br(esc($message)) . "</p>
            <p><a href='{$safeLink}' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Download Files</a></p>
            <p><small>Link: {$safeLink}</small></p>
        ";

        if (filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            $this->email->setReplyTo($sender);
        }
        $this->email->setTo($transfer['recipient_email']);
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        return $this->email->send();
    }

    public function sendDownloadNotification(array $share): bool
    {
        $recipient = $share['sender_email'] ?? null;
        if (!$recipient) return false;

        if (!$this->prepare($this->settingsService->getSettings())) {
            log_message('warning', 'Download notification skipped: email settings are not configured.');
            return false;
        }

        $subject = 'Your files were downloaded';
        $body = "
            <h2>Download Confirmation</h2>
            <p>The files you sent with subject <strong>" . esc($share['subject'] ?? 'No Subject') . "</strong> have been downloaded.</p>
        ";

        $this->email->setTo($recipient);
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        return $this->email->send();
    }

    public function sendExpiryReminder(array $share): bool
    {
        $settings = $this->settingsService->getSettings();
        if (!$this->prepare($settings)) return false;
        $this->email->setTo($share['sender_email']);
        $this->email->setSubject("Your files haven't been downloaded yet");
        $this->email->setMessage('<h2>Reminder</h2><p>The files you sent with subject <strong>'
            . esc((string)($share['subject'] ?? 'your transfer')) . '</strong> have not been downloaded yet.</p>');
        return $this->email->send();
    }

    public function getDebugger(): string
    {
        return lang('Mail.email_delivery_failed');
    }

    public function sendTestEmailWithConfig(string $recipient, array $settings): array
    {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false || !$this->prepare($settings)) {
            return ['ok' => false, 'error' => $this->lastError ?? $this->configurationError($settings) ?? 'invalid_email_settings'];
        }
        $this->email->setTo($recipient);
        $this->email->setSubject('eXtplorer Test Email');
        $this->email->setMessage('<h1>Test Email</h1><p>If you are reading this, your email settings are correct.</p>');
        try {
            $ok = $this->email->send();
        } catch (\Throwable) {
            $ok = false;
        }
        return ['ok' => $ok, 'error' => $ok ? null : 'email_delivery_failed'];
    }

    public function validateConfig(array $settings): array
    {
        if (($error = $this->configurationError($settings)) !== null) {
            return ['ok' => false, 'error' => $error];
        }
        try {
            $config = (new MailConfigPolicy())->deliveryConfig($settings);
        } catch (MailConfigurationException $error) {
            return ['ok' => false, 'error' => $error->reason];
        }
        if ($config['protocol'] !== 'smtp') return ['ok' => true];

        // Never return SMTP responses or exception messages: they may contain
        // credentials, remote-controlled text, or internal network details.
        $email = Services::email();
        $email->initialize($config);
        $end = null;
        try {
            $ref = new \ReflectionClass($email);
            $connect = $ref->getMethod('SMTPConnect');
            $auth = $ref->getMethod('SMTPAuthenticate');
            $end = $ref->getMethod('SMTPEnd');
            $ok = (bool)$connect->invoke($email) && (bool)$auth->invoke($email);
        } catch (\Throwable) {
            $ok = false;
        } finally {
            if ($end !== null) {
                try { $end->invoke($email); } catch (\Throwable) {}
            }
        }
        return ['ok' => $ok, 'error' => $ok ? null : 'email_validation_failed'];
    }

    public function configurationError(array $settings): ?string
    {
        try {
            (new MailConfigPolicy())->transportConfig($settings);
            return null;
        } catch (MailConfigurationException $error) {
            return $error->reason;
        }
    }

    /**
     * Return the current delivery readiness state without exposing secrets.
     *
     * @return array{ready: bool, configured: bool, reason: string, verified_at: ?string}
     */
    public function deliveryStatus(?array $settings = null): array
    {
        $settings ??= $this->settingsService->getSettings();
        $configured = $this->isConfigured($settings);
        $verifiedAt = is_string($settings['email_delivery_verified_at'] ?? null)
            ? $settings['email_delivery_verified_at']
            : null;

        if (!$configured) {
            return [
                'ready' => false,
                'configured' => false,
                'reason' => $this->configurationError($settings) ?? 'email_not_configured',
                'verified_at' => $verifiedAt,
            ];
        }

        $fingerprint = (string)($settings['email_delivery_verified_fingerprint'] ?? '');
        $ready = $fingerprint !== ''
            && hash_equals($this->configFingerprint($settings), $fingerprint)
            && $verifiedAt !== null
            && $verifiedAt !== '';

        return [
            'ready' => $ready,
            'configured' => true,
            'reason' => $ready ? 'verified' : 'test_required',
            'verified_at' => $verifiedAt,
        ];
    }

    public function isDeliveryReady(?array $settings = null): bool
    {
        return $this->deliveryStatus($settings)['ready'];
    }

    /**
     * Hash only the effective mail configuration. The password is included in
     * the hash so credential changes invalidate verification, but the secret
     * itself is never persisted in the verification record.
     */
    public function configFingerprint(array $settings): string
    {
        // Version the trust contract so pre-fix tests cannot authorize delivery.
        $config = (new MailConfigPolicy())->transportConfig($settings);
        return hash('sha256', 'mail-v2:' . json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function markDeliveryVerified(array $settings): string
    {
        $verifiedAt = gmdate(DATE_ATOM);
        $this->settingsService->saveSettings([
            'email_delivery_verified_fingerprint' => $this->configFingerprint($settings),
            'email_delivery_verified_at' => $verifiedAt,
        ]);

        return $verifiedAt;
    }

    public function invalidateDeliveryVerification(): void
    {
        $this->settingsService->saveSettings([
            'email_delivery_verified_fingerprint' => '',
            'email_delivery_verified_at' => null,
        ]);
    }

    public function isConfigured(array $settings): bool
    {
        return $this->configurationError($settings) === null;
    }
}
