<?php

namespace App\Services;

use RuntimeException;

final class SecretReader
{
    public static function environment(string $fileKey, string $legacyKey, bool $required = true): ?string
    {
        $file = getenv($fileKey);
        if ($file !== false && trim($file) !== '') {
            return self::file(trim($file));
        }

        $value = getenv($legacyKey);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if ($required) {
            throw new RuntimeException("Required secret is missing: {$fileKey}");
        }

        return null;
    }

    public static function file(string $path): string
    {
        if (!is_readable($path)) {
            throw new RuntimeException("Secret file is missing or unreadable: {$path}");
        }

        $value = file_get_contents($path);
        if ($value === false) {
            throw new RuntimeException("Unable to read secret file: {$path}");
        }

        // Secret files conventionally end with a newline; preserve all other
        // whitespace because it may be part of the secret.
        $value = rtrim($value, "\r\n");
        if ($value === '') {
            throw new RuntimeException("Secret file is empty: {$path}");
        }

        return $value;
    }

    public static function stdin(): string
    {
        $value = rtrim((string)stream_get_contents(STDIN), "\r\n");
        if ($value === '') {
            throw new RuntimeException('Password input is empty.');
        }

        return $value;
    }
}
