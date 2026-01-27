<?php

namespace App\Services;

use Config\Services;

class EmailService
{
    private $email;
    private SettingsService $settingsService;

    public function __construct()
    {
        $this->email = Services::email();
        $this->settingsService = new SettingsService();
        $this->initializeConfig();
    }

    private function initializeConfig()
    {
        $settings = $this->settingsService->getSettings();
        $this->email->initialize($this->buildConfig($settings));
    }

    private function buildConfig(array $settings): array
    {
        $protocol = $settings['email_protocol'] ?? 'smtp';
        $config = [
            'protocol' => $protocol,
            'mailType' => 'html',
            'charset'  => 'utf-8',
            'newline'  => "\r\n",
            'fromEmail' => $settings['email_from'] ?? 'noreply@example.com',
            'fromName'  => $settings['email_from_name'] ?? 'eXtplorer',
        ];

        if ($protocol === 'smtp') {
            $config['SMTPHost'] = $settings['smtp_host'] ?? '';
            $config['SMTPPort'] = (int)($settings['smtp_port'] ?? 587);
            $config['SMTPUser'] = $settings['smtp_user'] ?? '';
            $config['SMTPPass'] = $settings['smtp_pass'] ?? '';
            $config['SMTPCrypto'] = $settings['smtp_crypto'] ?? 'tls';
        } elseif ($protocol === 'sendmail') {
            $config['mailPath'] = $settings['sendmail_path'] ?? '/usr/sbin/sendmail';
        }

        return $config;
    }

    public function sendTestEmail(string $recipient): bool
    {
        if (!$this->isConfigured($this->settingsService->getSettings())) {
            log_message('warning', 'Email send skipped: email settings are not configured.');
            return false;
        }

        $this->email->setFrom($this->settingsService->get('email_from'), $this->settingsService->get('email_from_name'));
        $this->email->setTo($recipient);
        $this->email->setSubject('eXtplorer Test Email');
        $this->email->setMessage('<h1>Test Email</h1><p>If you are reading this, your email settings are correct.</p>');

        if ($this->email->send()) {
            return true;
        } else {
            // Log error
            log_message('error', $this->email->printDebugger(['headers']));
            return false;
        }
    }

    public function sendTransferNotification(array $transfer, string $link): bool
    {
        if (!$this->isConfigured($this->settingsService->getSettings())) {
            log_message('warning', 'Transfer email skipped: email settings are not configured.');
            return false;
        }

        $sender = $transfer['sender_email'] ?? 'Unknown';
        $message = $transfer['message'] ?? '';
        $subject = $transfer['subject'] ?? 'Files shared with you';
        
        // Simple HTML Template
        $body = "
            <h2>You received files!</h2>
            <p><strong>{$sender}</strong> sent you files via eXtplorer.</p>
            <p><strong>Message:</strong><br>" . nl2br(esc($message)) . "</p>
            <p><a href='{$link}' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Download Files</a></p>
            <p><small>Link: {$link}</small></p>
        ";

        $this->email->setFrom($this->settingsService->get('email_from'), $this->settingsService->get('email_from_name'));
        $this->email->setReplyTo($sender);
        $this->email->setTo($transfer['recipient_email']);
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        return $this->email->send();
    }

    public function sendDownloadNotification(array $share): bool
    {
        $recipient = $share['sender_email'] ?? null;
        if (!$recipient) return false;

        if (!$this->isConfigured($this->settingsService->getSettings())) {
            log_message('warning', 'Download notification skipped: email settings are not configured.');
            return false;
        }

        $subject = 'Your files were downloaded';
        $body = "
            <h2>Download Confirmation</h2>
            <p>The files you sent with subject <strong>" . esc($share['subject'] ?? 'No Subject') . "</strong> have been downloaded.</p>
        ";

        $this->email->setFrom($this->settingsService->get('email_from'), $this->settingsService->get('email_from_name'));
        $this->email->setTo($recipient);
        $this->email->setSubject($subject);
        $this->email->setMessage($body);

        return $this->email->send();
    }

    public function getDebugger(): string
    {
        return $this->email->printDebugger(['headers']);
    }

    public function sendTestEmailWithConfig(string $recipient, array $settings): array
    {
        if (!$this->isConfigured($settings)) {
            return [
                'ok' => false,
                'debug' => 'Email settings are not configured.',
            ];
        }

        $email = Services::email();
        $email->initialize($this->buildConfig($settings));
        $email->setFrom($settings['email_from'] ?? 'noreply@example.com', $settings['email_from_name'] ?? 'eXtplorer');
        $email->setTo($recipient);
        $email->setSubject('eXtplorer Test Email');
        $email->setMessage('<h1>Test Email</h1><p>If you are reading this, your email settings are correct.</p>');

        $ok = $email->send();
        return [
            'ok' => $ok,
            'debug' => $email->printDebugger(['headers']),
        ];
    }

    public function validateConfig(array $settings): array
    {
        if (!$this->isConfigured($settings)) {
            return ['ok' => false, 'message' => 'Email settings are not configured.'];
        }

        $protocol = $settings['email_protocol'] ?? 'smtp';
        if ($protocol === 'sendmail') {
            $path = $settings['sendmail_path'] ?? '/usr/sbin/sendmail';
            if (!is_file($path) || !is_executable($path)) {
                return ['ok' => false, 'message' => "Sendmail not available at {$path}"];
            }
            return ['ok' => true, 'message' => 'Sendmail path is available'];
        }

        if ($protocol === 'mail') {
            return ['ok' => true, 'message' => 'PHP mail() selected'];
        }

        $email = Services::email();
        $email->initialize($this->buildConfig($settings));

        try {
            $ref = new \ReflectionClass($email);
            $connect = $ref->getMethod('SMTPConnect');
            $connect->setAccessible(true);
            $auth = $ref->getMethod('SMTPAuthenticate');
            $auth->setAccessible(true);
            $end = $ref->getMethod('SMTPEnd');
            $end->setAccessible(true);

            $connected = (bool) $connect->invoke($email);
            if (!$connected) {
                return ['ok' => false, 'message' => $email->printDebugger(['headers'])];
            }

            $authed = true;
            if (!empty($settings['smtp_user']) || !empty($settings['smtp_pass'])) {
                $authed = (bool) $auth->invoke($email);
            }

            $end->invoke($email);

            if (!$authed) {
                return ['ok' => false, 'message' => $email->printDebugger(['headers'])];
            }

            return ['ok' => true, 'message' => 'SMTP connection and authentication successful'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function isConfigured(array $settings): bool
    {
        $from = trim((string)($settings['email_from'] ?? ''));
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $protocol = strtolower(trim((string)($settings['email_protocol'] ?? 'smtp')));
        if ($protocol === 'sendmail') {
            $path = trim((string)($settings['sendmail_path'] ?? '/usr/sbin/sendmail'));
            return $path !== '' && is_file($path) && is_executable($path);
        }

        if ($protocol === 'mail') {
            return true;
        }

        // Default to SMTP requirements.
        $host = trim((string)($settings['smtp_host'] ?? ''));
        $port = (int)($settings['smtp_port'] ?? 0);
        return $host !== '' && $port > 0;
    }
}
