<?php

namespace App\Services;

use Config\Services;

/**
 * Limits mail that other people can trigger through the application, so
 * anonymous downloads and transfers cannot flood mailboxes or the sender's
 * reputation.
 */
final class MailRatePolicy
{
    public const TRANSFER_RECIPIENTS_PER_HOUR = 100;

    /** One download notification per share and hour. */
    public function allowDownloadNotification(string $shareHash): bool
    {
        return Services::throttler()->check('mail-download-' . hash('sha256', $shareHash), 1, HOUR);
    }

    /** Each recipient of a transfer counts against the sending account's hourly budget. */
    public function allowTransferRecipients(string $username, int $recipients): bool
    {
        return Services::throttler()->check(
            'mail-transfer-' . hash('sha256', $username),
            self::TRANSFER_RECIPIENTS_PER_HOUR,
            HOUR,
            max(1, $recipients)
        );
    }
}
