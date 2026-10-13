<?php

namespace App\Services\VFS;

use App\Services\FileNamePolicy;
use App\Services\ResourcePolicy;
use Exception;

class LocalAdapter implements IFileSystem, StreamWritableFileSystem, LocalPathFileSystem
{
    private string $rootPath;
    private ResourcePolicy $resourcePolicy;

    public function __construct(string $rootPath, ?ResourcePolicy $resourcePolicy = null)
    {
        $this->resourcePolicy = $resourcePolicy ?? new ResourcePolicy();
        // Normalize WSL paths on Windows
        if (DIRECTORY_SEPARATOR === '\\' && preg_match('|^/mnt/([a-z])/(.*)|i', $rootPath, $matches)) {
            $rootPath = strtoupper($matches[1]) . ':/' . $matches[2];
        }

        $realRoot = realpath($rootPath);
        $this->rootPath = $realRoot === false ? '' : rtrim($realRoot, DIRECTORY_SEPARATOR);
        if ($this->rootPath === '' || !is_dir($this->rootPath)) {
            throw new Exception("Invalid root path: $rootPath");
        }
    }

    public function resolvePath(string $path): string
    {
        return PathPolicy::resolve($this->rootPath, $path);
    }

    public function resolveLocalPath(string $path): ?string
    {
        return $this->resolvePath($path);
    }

    public function writeStream(string $path, $source): bool
    {
        $this->assertWritableTarget($path);
        $target = $this->resolvePath($path);
        $temporary = tempnam(dirname($target), '.extplorer-write-');
        if ($temporary === false) throw new Exception('Unable to create write staging file.');
        $output = null;
        try {
            $output = fopen($temporary, 'wb');
            $this->resourcePolicy->copyStream($source, $output);
            fclose($output);
            $output = null;
            $this->resolvePath($path); // Revalidate the jail immediately before activation.
            return rename($temporary, $target);
        } finally {
            if (is_resource($output)) fclose($output);
            if (is_file($temporary)) unlink($temporary);
        }
    }

    private function isWithinRoot(string $fullPath): bool
    {
        return PathPolicy::isWithinRoot($this->rootPath, $fullPath);
    }

    public function listDirectory(string $path, bool $showHidden = true): array
    {
        $fullPath = $this->resolvePath($path);
        if (!is_dir($fullPath)) {
            throw new Exception("Directory not found: $path");
        }

        $result = [];
        $handle = opendir($fullPath);
        if ($handle === false) {
            throw new Exception("Unable to read directory: $path");
        }

        try {
            while (($item = readdir($handle)) !== false) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                if (!$showHidden && str_starts_with($item, '.')) {
                    continue;
                }
                if (count($result) >= $this->resourcePolicy->maxDirectoryEntries()) {
                    throw new Exception('Directory listing exceeds the configured resource limit.');
                }

                $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
                if (is_link($itemPath)) {
                    continue;
                }
                $relativePath = $path === '/' || $path === '' ? $item : $path . '/' . $item;
                $result[] = $this->getMetadataInternal($itemPath, $relativePath);
            }
        } finally {
            closedir($handle);
        }

        return $result;
    }

    public function readFile(string $path): string
    {
        $fullPath = $this->resolvePath($path);
        if (!is_file($fullPath)) {
            throw new Exception("File not found: $path");
        }
        $stream = fopen($fullPath, 'rb');
        if ($stream === false) {
            throw new Exception("Unable to read file: $path");
        }
        try {
            return $this->resourcePolicy->readStream($stream);
        } finally {
            fclose($stream);
        }
    }

    public function openReadStream(string $path)
    {
        $stream = fopen($this->resolvePath($path), 'rb');
        if ($stream === false) {
            throw new Exception("Unable to open file: {$path}");
        }

        return $stream;
    }

    public function writeFile(string $path, string $content): bool
    {
        $this->assertWritableTarget($path);
        $fullPath = $this->resolvePath($path);
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            return false;
        }

        $temporary = tempnam($directory, '.extplorer-write-');
        if ($temporary === false) {
            return false;
        }

        try {
            if (file_put_contents($temporary, $content, LOCK_EX) === false) {
                return false;
            }

            return rename($temporary, $fullPath);
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->resolvePath($path);
        if (is_link($fullPath)) {
            return false;
        }
        if (is_dir($fullPath)) {
            return $this->deleteDirectory($fullPath);
        } elseif (is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir) || is_link($dir)) {
            return false;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_link($path) || is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->deleteDirectory($path);
            }
        }
        return rmdir($dir);
    }

    public function createDirectory(string $path): bool
    {
        $this->assertWritableTarget($path);
        $fullPath = $this->resolvePath($path);
        if (file_exists($fullPath)) {
            return false;
        }
        return mkdir($fullPath, 0755, true);
    }

    public function rename(string $from, string $to): bool
    {
        $this->assertWritableTarget($to);
        $fullFrom = $this->resolvePath($from);
        $fullTo = $this->resolvePath($to);
        return rename($fullFrom, $fullTo);
    }

    public function move(string $from, string $to): bool
    {
        return $this->rename($from, $to);
    }

    public function copy(string $from, string $to): bool
    {
        $this->assertWritableTarget($to);
        $fullFrom = $this->resolvePath($from);
        $fullTo = $this->resolvePath($to);

        if (is_dir($fullFrom)) {
            $this->assertValidCopyTarget($fullFrom, $fullTo);
            return $this->recurseCopy($fullFrom, $fullTo);
        } else {
            return copy($fullFrom, $fullTo);
        }
    }

    private function assertValidCopyTarget(string $src, string $dst): void
    {
        $source = $this->normalizePathForComparison($src);
        $target = $this->normalizePathForComparison($dst);

        if ($source === $target) {
            throw new Exception('Cannot copy a directory onto itself.');
        }

        if (str_starts_with($target, $source . '/')) {
            throw new Exception('Cannot copy a directory into one of its descendants.');
        }
    }

    private function normalizePathForComparison(string $path): string
    {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');

        if (DIRECTORY_SEPARATOR === '\\') {
            $normalized = strtolower($normalized);
        }

        return $normalized;
    }

    private function recurseCopy(string $src, string $dst): bool
    {
        $dir = opendir($src);
        if (!is_dir($dst) && !mkdir($dst, 0755, true) && !is_dir($dst)) {
            return false;
        }
        while (false !== ($file = readdir($dir))) {
            if ($file !== '.' && $file !== '..') {
                (new FileNamePolicy())->assertSafe($file);
                $srcPath = $src . '/' . $file;
                $dstPath = $dst . '/' . $file;

                if (is_link($srcPath)) {
                    throw new Exception('Refusing to copy symbolic links.');
                }

                if (is_dir($srcPath)) {
                    if (!$this->recurseCopy($srcPath, $dstPath)) {
                        closedir($dir);
                        return false;
                    }
                } else {
                    if (!copy($srcPath, $dstPath)) {
                        closedir($dir);
                        return false;
                    }
                }
            }
        }
        closedir($dir);
        return true;
    }

    private function assertWritableTarget(string $path): void
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        if ($normalized === '') {
            throw new Exception('A target filename is required.');
        }

        (new FileNamePolicy())->assertSafePath($normalized);
    }

    public function archive(array $sources, string $destination): bool
    {
        return (new LocalArchiveService($this, $this->resourcePolicy))->archive($sources, $destination);
    }

    public function extract(string $archive, string $destination): bool
    {
        return (new LocalArchiveService($this, $this->resourcePolicy))->extract($archive, $destination);
    }

    public function getMetadata(string $path): ?array
    {
        $fullPath = $this->resolvePath($path);
        if (!file_exists($fullPath)) {
            return null;
        }
        return $this->getMetadataInternal($fullPath, $path);
    }

    public function chmod(string $path, int $mode, bool $recursive = false): bool
    {
        $fullPath = $this->resolvePath($path);
        if (!file_exists($fullPath)) {
            throw new Exception("File not found: $path");
        }

        if ($recursive && is_dir($fullPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $item) {
                if ($item->isLink()) {
                    continue;
                }
                chmod($item->getPathname(), $mode);
            }
        }

        return chmod($fullPath, $mode);
    }

    public function chown(string $path, $user, $group, bool $recursive = false): bool
    {
        $fullPath = $this->resolvePath($path);
        if (!file_exists($fullPath)) {
            throw new Exception("File not found: $path");
        }

        $apply = function($p) use ($user, $group) {
            $res = true;
            if ($user) $res = $res && chown($p, $user);
            if ($group) $res = $res && chgrp($p, $group);
            return $res;
        };

        if ($recursive && is_dir($fullPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $item) {
                if ($item->isLink()) {
                    continue;
                }
                $apply($item->getPathname());
            }
        }

        return $apply($fullPath);
    }

    public function search(string $query): array
    {
        $budget = $this->resourcePolicy->startOperation();
        $results = [];
        $dir = new \RecursiveDirectoryIterator($this->rootPath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);

        $visited = 0;
        foreach ($iterator as $file) {
            $budget->tick();
            if (++$visited > $this->resourcePolicy->maxDirectoryEntries()) {
                throw new Exception('Search exceeds the configured resource limit.');
            }
            if ($file->isLink()) {
                continue;
            }
            if (stripos($file->getFilename(), $query) !== false) {
                // Calculate relative path
                $fullPath = $file->getPathname();
                // Ensure it's within root (redundant given Iterator start, but good practice)
                if (str_starts_with($fullPath, $this->rootPath)) {
                    $relativePath = substr($fullPath, strlen($this->rootPath) + 1);
                    // Fix windows slashes
                    $relativePath = str_replace('\\', '/', $relativePath);

                    $results[] = $this->getMetadataInternal($fullPath, $relativePath);
                    $this->resourcePolicy->assertSearchResults(count($results));
                }
            }
        }
        return $results;
    }

    public function getDirectorySize(string $path): int
    {
        $budget = $this->resourcePolicy->startOperation();
        $fullPath = $this->resolvePath($path);
        if (!is_dir($fullPath)) return 0;

        $size = 0;
        $visited = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $budget->tick();
            if (++$visited > $this->resourcePolicy->maxDirectoryEntries()) {
                throw new Exception('Directory size exceeds the configured resource limit.');
            }
            if ($file->isLink()) {
                continue;
            }
            $size += $file->getSize();
        }
        return $size;
    }

    private function getMetadataInternal(string $fullPath, string $relativePath): array
    {
        if (is_link($fullPath)) {
            throw new Exception('Symbolic links are not supported.');
        }

        $isDir = is_dir($fullPath);
        $owner = 'unknown';
        $group = 'unknown';

        if (function_exists('posix_getpwuid')) {
            $ownerData = posix_getpwuid(fileowner($fullPath));
            $owner = $ownerData['name'] ?? $ownerData['uid'];
            $groupData = posix_getgrgid(filegroup($fullPath));
            $group = $groupData['name'] ?? $groupData['gid'];
        }

        $name = basename($fullPath);
        // Ensure valid UTF-8 for JSON compatibility
        if (!mb_check_encoding($name, 'UTF-8')) {
            $name = mb_convert_encoding($name, 'UTF-8', 'ISO-8859-1');
        }

        return [
            'name' => $name,
            'path' => $relativePath,
            'type' => $isDir ? 'dir' : 'file',
            'size' => $isDir ? 0 : filesize($fullPath),
            'mtime' => filemtime($fullPath),
            'perms' => substr(sprintf('%o', fileperms($fullPath)), -4),
            'owner' => $owner,
            'group' => $group,
            'extension' => $isDir ? null : pathinfo($fullPath, PATHINFO_EXTENSION),
            'mime' => $isDir ? 'directory' : mime_content_type($fullPath),
        ];
    }
}
