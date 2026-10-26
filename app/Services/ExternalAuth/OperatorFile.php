<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use RuntimeException;

/** Operator code/configuration must never be loaded from application-managed data. */
final class OperatorFile
{
    public static function path(string $path): string
    {
        $real = realpath($path);
        if ($real === false || !is_readable($real)) throw new RuntimeException('External authentication operator file is unavailable.');
        $storage = config('Storage');
        foreach ([ROOTPATH . 'public', $storage->root, $storage->fileManagerRoot, $storage->shared,
            $storage->state, $storage->uploads, $storage->session, $storage->cache, $storage->runtime,
            $storage->trash, $storage->versions, $storage->backups, $storage->logs] as $root) {
            $boundary = rtrim(realpath($root) ?: $root, DIRECTORY_SEPARATOR);
            if ($real === $boundary || str_starts_with($real, $boundary . DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('External authentication operator files overlap managed storage.');
            }
        }
        return $real;
    }
}
