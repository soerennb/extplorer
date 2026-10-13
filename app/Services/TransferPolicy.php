<?php

namespace App\Services;

use RuntimeException;

/** Normalizes the existing transfer request and its configured limits. */
final class TransferPolicy
{
    private array $settings;

    public function __construct(?SettingsService $settings = null)
    {
        $this->settings = ($settings ?? new SettingsService())->getSettings();
    }

    public function sessionId(string $id): string
    {
        $id = preg_replace('/[^a-zA-Z0-9]/', '', $id) ?? '';
        return strlen($id) >= 16 && strlen($id) <= 64 ? $id : '';
    }

    public function filename(string $filename): string
    {
        return (new FileNamePolicy())->assertSafe(basename(str_replace('\\', '/', $filename)));
    }

    public function recipients(mixed $values): array
    {
        if (!is_array($values)) return [];
        $result = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) continue;
            $email = strtolower(trim((string)$value));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) $result[$email] = true;
            if (count($result) === 25) break;
        }
        return array_keys($result);
    }

    public function expiryDays(int $days): int
    {
        $maximum = max(1, min(365, (int)($this->settings['transfer_max_expiry_days'] ?? 30)));
        return max(1, min($maximum, $days > 0 ? $days : (int)($this->settings['default_transfer_expiry'] ?? 7)));
    }

    public function maxFileBytes(): int
    {
        return max(0, min(10240, (int)($this->settings['upload_max_file_mb'] ?? 0))) * 1024 * 1024;
    }

    public function quotaBytes(): int
    {
        return max(0, min(102400, (int)($this->settings['quota_per_user_mb'] ?? 0))) * 1024 * 1024;
    }

    public function request(array $input, ?string $sender): array
    {
        $id = $this->sessionId(is_scalar($input['sessionId'] ?? null) ? (string)$input['sessionId'] : '');
        $recipients = $this->recipients($input['recipients'] ?? []);
        if ($id === '' || $recipients === []) throw new RuntimeException('Missing parameters');
        if (!is_string($input['subject'] ?? '') || !is_string($input['message'] ?? '')) throw new RuntimeException('Invalid transfer message.');
        $subject = trim((string)preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', $input['subject'] ?? ''));
        $message = trim($input['message'] ?? '');
        if (mb_strlen($subject) > 200) throw new RuntimeException('Email subject is too long.');
        if (mb_strlen($message) > 20000) throw new RuntimeException('Email message is too long.');
        return [
            'session_id' => $id, 'recipients' => $recipients, 'subject' => $subject, 'message' => $message,
            'expires_at' => time() + 86400 * $this->expiryDays((int)($input['expiresIn'] ?? 0)),
            'notify_download' => (bool)($input['notifyDownload'] ?? $this->settings['transfer_default_notify_download'] ?? false),
            'sender_email' => filter_var($sender, FILTER_VALIDATE_EMAIL) ? $sender : null,
        ];
    }
}
