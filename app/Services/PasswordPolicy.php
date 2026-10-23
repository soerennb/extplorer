<?php

namespace App\Services;

final class PasswordPolicy
{
    public const MIN_LENGTH = 12;
    public const MAX_BYTES = 72;

    public static function validate(string $password): ?string
    {
        if (strlen($password) < self::MIN_LENGTH) {
            return 'Password must be at least 12 characters long.';
        }

        // bcrypt (PASSWORD_DEFAULT) ignores bytes beyond 72.
        if (strlen($password) > self::MAX_BYTES) {
            return 'Password must not be longer than 72 bytes.';
        }

        if (trim($password) === '') {
            return 'Password must contain a non-whitespace character.';
        }

        return null;
    }
}
