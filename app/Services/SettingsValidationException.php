<?php

namespace App\Services;

final class SettingsValidationException extends \InvalidArgumentException
{
    public function __construct(string $message, int $status = 400, public readonly ?string $errorKey = null)
    {
        parent::__construct($message, $status);
    }
}
