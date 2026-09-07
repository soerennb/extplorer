<?php

namespace App\Services\VFS;

use App\Services\ResourcePolicy;
use Exception;
use ZipArchive;
use PharData;

class LocalAdapter implements IFileSystem
{
    private string $rootPath;
    private ResourcePolicy $resourcePolicy;
    private ?\App\Services\OperationBudget $operationBudget = null;
    private int $archiveEntries = 0;
    private int $archiveBytes = 0;

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
        if (is_dir($fullPath)) {
            return $this->deleteDirectory($fullPath);
        } elseif (is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
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
        $fullPath = $this->resolvePath($path);
        if (file_exists($fullPath)) {
            return false;
        }
        return mkdir($fullPath, 0755, true);
    }

    public function rename(string $from, string $to): bool
    {
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

    public function archive(array $sources, string $destination): bool
    {
        $this->operationBudget = $this->resourcePolicy->startOperation();
        $this->archiveEntries = 0;
        $this->archiveBytes = 0;
        $fullDest = $this->resolvePath($destination);
        $ext = strtolower(pathinfo($fullDest, PATHINFO_EXTENSION));
        
        if (str_ends_with(strtolower($fullDest), '.tar.gz')) {
            $ext = 'tar.gz';
        }

        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($fullDest, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Cannot create zip file: $destination");
            }
            foreach ($sources as $source) {
                $fullSource = $this->resolvePath($source);
                $baseName = basename($fullSource);
                if (is_dir($fullSource)) $this->addDirToZip($zip, $fullSource, $baseName);
                else {
                    $this->reserveArchive(is_file($fullSource) ? (int)filesize($fullSource) : 0);
                    if (!$zip->addFile($fullSource, $baseName)) {
                        throw new Exception("Unable to add archive source: {$source}");
                    }
                }
            }
            return $zip->close();
        } else if ($ext === 'tar' || $ext === 'tar.gz') {
            if (file_exists($fullDest)) unlink($fullDest);
            $archiveName = $ext === 'tar.gz' ? str_replace('.tar.gz', '.tar', $fullDest) : $fullDest;
            $phar = new PharData($archiveName);
            foreach ($sources as $source) {
                $fullSource = $this->resolvePath($source);
                if (is_dir($fullSource)) {
                    $this->reserveTree($fullSource);
                    $phar->buildFromDirectory($fullSource);
                } else {
                    $this->reserveArchive((int)(filesize($fullSource) ?: 0));
                    $phar->addFile($fullSource, basename($fullSource));
                }
            }
            if ($ext === 'tar.gz') {
                $phar->compress(\Phar::GZ);
                unlink($archiveName);
            }
            return true;
        }
        throw new Exception("Unsupported archive format: $ext");
    }

    private function addDirToZip(ZipArchive $zip, string $dir, string $localPath)
    {
        $this->operationBudget?->tick();
        $this->reserveArchive();
        $zip->addEmptyDir($localPath);
        $files = scandir($dir);
        foreach ($files as $file) {
            $this->operationBudget?->tick();
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            $newLocalPath = $localPath . '/' . $file;
            if (is_link($fullPath)) {
                throw new Exception('Refusing to archive symbolic links.');
            }
            if (is_dir($fullPath)) {
                $this->addDirToZip($zip, $fullPath, $newLocalPath);
            } else {
                $this->reserveArchive((int)(filesize($fullPath) ?: 0));
                if (!$zip->addFile($fullPath, $newLocalPath)) {
                    throw new Exception("Unable to add archive source: {$fullPath}");
                }
            }
        }
    }

    private function reserveArchive(int $bytes = 0): void
    {
        $this->archiveEntries++;
        $this->archiveBytes += max(0, $bytes);
        if ($this->archiveEntries > $this->resourcePolicy->maxArchiveEntries()
            || $this->archiveBytes > $this->resourcePolicy->maxArchiveBytes()) {
            throw new Exception('Archive exceeds the configured safety limits.');
        }
    }

    private function reserveTree(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $this->operationBudget?->tick();
            if ($item->isLink()) {
                throw new Exception('Refusing to archive symbolic links.');
            }
            $this->reserveArchive($item->isFile() ? (int)($item->getSize() ?: 0) : 0);
        }
    }

    public function extract(string $archive, string $destination): bool
    {
        $fullArchive = $this->resolvePath($archive);
        $fullDest = $this->resolvePath($destination);
        $ext = strtolower(pathinfo($fullArchive, PATHINFO_EXTENSION));

        if (str_ends_with(strtolower($fullArchive), '.tar.gz')) {
            $ext = 'tar.gz';
        }

        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($fullArchive) === true) {
                $expanded = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = (string)$zip->getNameIndex($i);
                    $this->assertSafeArchiveEntryPath($entryName);
                    $this->buildSafeExtractionPath($fullDest, $entryName);
                    if ($this->isZipEntrySymlink($zip, $i)) {
                        throw new Exception('Archive contains symbolic link entries.');
                    }
                    $stat = $zip->statIndex($i);
                    $size = max(0, (int)($stat['size'] ?? 0));
                    $compressed = max(0, (int)($stat['comp_size'] ?? 0));
                    $expanded += $size;
                    if ($i + 1 > $this->resourcePolicy->maxArchiveEntries()
                        || $expanded > $this->resourcePolicy->maxArchiveBytes()
                        || ($compressed > 0 && $size > $compressed * $this->resourcePolicy->maxArchiveRatio())) {
                        throw new Exception('Archive exceeds the configured safety limits.');
                    }
                }

                $zip->extractTo($fullDest);
                $zip->close();
                return true;
            }
        } else if ($ext === 'tar' || $ext === 'tar.gz') {
            $phar = new PharData($fullArchive);
            return $this->extractTarSafely($phar, $fullDest);
        }

        throw new Exception("Failed to open or unsupported archive: $archive");
    }

    private function extractTarSafely(PharData $phar, string $destination): bool
    {
        $iterator = new \RecursiveIteratorIterator($phar, \RecursiveIteratorIterator::SELF_FIRST);

        $entries = 0;
        $expanded = 0;
        foreach ($iterator as $internalPath => $entry) {
            if (!$entry instanceof \PharFileInfo) {
                continue;
            }

            $entryPath = (string)$internalPath;
            $entries++;
            $expanded += max(0, (int)$entry->getSize());
            if ($entries > $this->resourcePolicy->maxArchiveEntries() || $expanded > $this->resourcePolicy->maxArchiveBytes()) {
                throw new Exception('Archive exceeds the configured safety limits.');
            }
            $this->assertSafeArchiveEntryPath($entryPath);
            $targetPath = $this->buildSafeExtractionPath($destination, $entryPath);

            if ($entry->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new Exception("Failed to create extraction directory: {$targetPath}");
                }
                continue;
            }

            // Skip links to avoid extracting pointers that can escape root on later operations.
            if ($entry->isLink()) {
                throw new Exception('Archive contains symbolic link entries.');
            }

            $parent = dirname($targetPath);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new Exception("Failed to create extraction directory: {$parent}");
            }

            $readStream = $entry->openFile('r');
            $handle = fopen($targetPath, 'wb');
            if ($handle === false) {
                throw new Exception("Failed to write extracted file: {$targetPath}");
            }

            while (!$readStream->eof()) {
                fwrite($handle, (string)$readStream->fread(8192));
            }
            fclose($handle);
        }

        return true;
    }

    private function isZipEntrySymlink(ZipArchive $zip, int $index): bool
    {
        $stat = $zip->statIndex($index);
        if (!is_array($stat)) {
            return false;
        }

        $opsys = (int)($stat['opsys'] ?? 0);
        $externalAttributes = (int)($stat['external_attributes'] ?? 0);

        // Only Unix attributes reliably encode file type bits for symlinks.
        if ($opsys !== ZipArchive::OPSYS_UNIX || $externalAttributes === 0) {
            return false;
        }

        $mode = ($externalAttributes >> 16) & 0xF000;
        return $mode === 0xA000;
    }

    private function assertSafeArchiveEntryPath(string $entryName): void
    {
        $entryName = str_replace('\\', '/', trim($entryName));
        if ($entryName === '') {
            throw new Exception('Archive contains an empty entry path.');
        }

        if ($entryName[0] === '/' || preg_match('/^[a-zA-Z]:\//', $entryName)) {
            throw new Exception('Archive contains an absolute entry path.');
        }

        $parts = explode('/', $entryName);
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                throw new Exception('Archive contains path traversal entries.');
            }
        }
    }

    private function buildSafeExtractionPath(string $destination, string $entryName): string
    {
        $normalized = trim(str_replace('\\', '/', $entryName), '/');
        $parts = array_values(array_filter(
            explode('/', $normalized),
            static fn(string $part): bool => $part !== '' && $part !== '.'
        ));

        $candidate = rtrim($destination, DIRECTORY_SEPARATOR);
        if (!empty($parts)) {
            $candidate .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
        }

        $this->assertWithinDirectory($candidate, $destination);

        return $candidate;
    }

    private function assertWithinDirectory(string $candidate, string $baseDir): void
    {
        $base = rtrim(realpath($baseDir) ?: $baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $check = $candidate;

        while (!file_exists($check)) {
            $parent = dirname($check);
            if ($parent === $check) {
                break;
            }
            $check = $parent;
        }

        $resolved = realpath($check);
        if ($resolved === false) {
            throw new Exception('Invalid extraction path.');
        }

        $resolved = rtrim($resolved, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($resolved, $base)) {
            throw new Exception('Archive extraction path traversal blocked.');
        }
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
