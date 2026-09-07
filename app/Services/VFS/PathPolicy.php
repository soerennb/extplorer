<?php

namespace App\Services\VFS;

use RuntimeException;

/**
 * Resolves user supplied paths inside a fixed local filesystem scope.
 *
 * Paths are root-relative. A leading slash is accepted for compatibility with
 * the existing UI, but drive prefixes, traversal segments and symlink
 * components are never accepted.
 */
final class PathPolicy
{
    public static function normalizeRelative(string $path): string
    {
        if (str_contains($path, "\0")) {
            throw new RuntimeException('Invalid path.');
        }

        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, '//') || preg_match('/\A[A-Za-z]:\//', $normalized)) {
            throw new RuntimeException('Invalid absolute path.');
        }

        $parts = [];
        foreach (explode('/', $normalized) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                throw new RuntimeException('Path traversal blocked.');
            }

            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    public static function resolve(string $rootPath, string $path): string
    {
        $root = realpath($rootPath);
        if ($root === false || !is_dir($root)) {
            throw new RuntimeException('Invalid root path.');
        }

        $root = rtrim($root, DIRECTORY_SEPARATOR);
        $relative = self::normalizeRelative($path);
        $candidate = $root . ($relative === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));

        self::assertNoSymlinkComponents($root, $relative);

        $real = realpath($candidate);
        if ($real !== false) {
            if (!self::isWithinRoot($root, $real)) {
                throw new RuntimeException('Path traversal blocked.');
            }

            return $real;
        }

        $existingParent = self::findExistingParent($candidate);
        if ($existingParent === false || !self::isWithinRoot($root, $existingParent)) {
            throw new RuntimeException('Invalid path.');
        }

        return $candidate;
    }

    public static function isWithinRoot(string $rootPath, string $path): bool
    {
        $root = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        return $path === $root || str_starts_with($path, $root . DIRECTORY_SEPARATOR);
    }

    private static function assertNoSymlinkComponents(string $root, string $relative): void
    {
        $current = $root;
        if ($relative === '') {
            return;
        }

        foreach (explode('/', $relative) as $part) {
            $current .= DIRECTORY_SEPARATOR . $part;

            if (is_link($current)) {
                throw new RuntimeException('Symbolic links are not allowed in managed paths.');
            }

            if (!file_exists($current)) {
                break;
            }
        }
    }

    private static function findExistingParent(string $path): string|false
    {
        $current = $path;
        while (!file_exists($current) && !is_link($current)) {
            $parent = dirname($current);
            if ($parent === $current) {
                return false;
            }
            $current = $parent;
        }

        if (is_link($current)) {
            return false;
        }

        $real = realpath($current);
        return $real === false ? false : $real;
    }
}
