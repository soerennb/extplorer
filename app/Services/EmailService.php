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

        $config = [
            'protocol' => 'smtp',
            'SMTPHost' => $settings['smtp_host'],
            'SMTPPort' => (int)$settings['smtp_port'],
            'SMTPUser' => $settings['smtp_user'],
            'SMTPPass' => $settings['smtp_pass'],
            'SMTPCrypto' => $settings['smtp_crypto'],
            'mailType' => 'html',
            'charset'  => 'utf-8',
            'newline'  => "\r\n",
            'fromEmail' => $settings['email_from'],
            'fromName'  => $settings['email_from_name'],
        ];

        // If no host configured, fallback to standard mail() or log?
        // For now, we apply what we have.
        $this->email->initialize($config);
    }

    public function sendTestEmail(string $recipient): bool
    {
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
}
