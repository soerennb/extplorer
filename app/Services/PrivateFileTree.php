<?php

namespace App\Services;

use RuntimeException;

/** Operations on private staging trees; paths must already belong to the caller's scope. */
final class PrivateFileTree
{
    public static function assertNoSymlinks(string $path): void
    {
        $current = rtrim($path, '/\\');
        while ($current !== dirname($current)) {
            if (is_link($current)) throw new RuntimeException('Private storage uses a symbolic link.');
            $current = dirname($current);
        }
    }

    public static function remove(string $path): void
    {
        self::assertNoSymlinks($path);
        if (!is_dir($path)) return;
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $child = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($child) && !is_link($child)) {
                self::remove($child);
            } elseif (!unlink($child)) {
                throw new RuntimeException('Unable to remove private staging file.');
            }
        }
        if (!rmdir($path)) throw new RuntimeException('Unable to remove private staging directory.');
    }
}
