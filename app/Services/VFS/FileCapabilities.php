<?php

namespace App\Services\VFS;

final class FileCapabilities
{
    public static function localPath(IFileSystem $fs, string $path): ?string
    {
        return $fs instanceof LocalPathFileSystem ? $fs->resolveLocalPath($path) : null;
    }

    public static function forPath(IFileSystem $fs, string $path): array
    {
        $target = $fs;
        if ($fs instanceof VirtualAdapter) {
            if (trim($path, '/\\') === '') return ['upload' => false, 'edit' => false, 'trash' => false, 'versions' => false, 'share' => false, 'thumbnail' => false];
            [$target] = $fs->resolveTarget($path);
        }
        $local = $target instanceof LocalPathFileSystem;
        $write = $target instanceof StreamWritableFileSystem;
        return ['upload' => $write, 'edit' => !($target instanceof DeniedFileSystem), 'trash' => $local, 'versions' => $local, 'share' => $local, 'thumbnail' => $local];
    }
}
