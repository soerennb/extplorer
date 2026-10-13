<?php

namespace App\Services;

use RuntimeException;

/** Stable sidecar locks shared by operations and their cleanup paths. */
final class OperationLock
{
    public static function run(string $path, callable $callback): mixed
    {
        PrivateFileTree::assertNoSymlinks($path);
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Unable to create operation lock storage.');
        PrivateFileTree::assertNoSymlinks($path);
        $lock = fopen($path, 'c');
        if ($lock === false) throw new RuntimeException('Unable to open operation lock.');
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException('Unable to acquire operation lock.');
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            // Do not unlink: waiters must continue using the same lock inode.
        }
    }
}
