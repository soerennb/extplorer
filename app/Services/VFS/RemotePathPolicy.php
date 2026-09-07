<?php

namespace App\Services\VFS;

use RuntimeException;

final class RemotePathPolicy
{
    public static function normalizeRoot(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || $path === '/') {
            return '/';
        }
        if (str_contains($path, "\0") || self::hasControlCharacters($path)) {
            throw new RuntimeException('Remote root contains invalid characters.');
        }

        $segments = self::segments($path);
        return '/' . implode('/', $segments);
    }

    public static function normalizeRelative(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if (str_contains($path, "\0") || self::hasControlCharacters($path)) {
            throw new RuntimeException('Remote path contains invalid characters.');
        }
        return implode('/', self::segments($path));
    }

    private static function segments(string $path): array
    {
        $segments = [];
        foreach (explode('/', trim($path, '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                throw new RuntimeException('Remote path traversal is not allowed.');
            }
            $segments[] = $segment;
        }
        return $segments;
    }

    private static function hasControlCharacters(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
    }
}
