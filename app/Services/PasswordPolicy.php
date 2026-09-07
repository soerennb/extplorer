<?php

namespace App\Services;

final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    public static function validate(string $password): ?string
    {
        if (strlen($password) < self::MIN_LENGTH) {
            return 'Password must be at least 12 characters long.';
        }

        if (trim($password) === '') {
            return 'Password must contain a non-whitespace character.';
        }

        return null;
    }
}
