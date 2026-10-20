<?php

namespace App\Services;

/** Only stable public reason codes leave the mail configuration boundary. */
final class MailConfigurationException extends \RuntimeException
{
    public function __construct(public readonly string $reason = 'invalid_email_settings')
    {
        parent::__construct($reason);
    }
}
