<?php

namespace App\Services\VFS;

use CodeIgniter\Files\File;
use Exception;
use ZipArchive;
use PharData;

class LocalAdapter implements IFileSystem
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        // Normalize WSL paths on Windows
        if (DIRECTORY_SEPARATOR === '\\' && preg_match('|^/mnt/([a-z])/(.*)|i', $rootPath, $matches)) {
            $rootPath = strtoupper($matches[1]) . ':/' . $matches[2];
        }

        $this->rootPath = rtrim(realpath($rootPath), DIRECTORY_SEPARATOR);
        if (!$this->rootPath || !is_dir($this->rootPath)) {
            throw new Exception("Invalid root path: $rootPath");
        }
    }

    public function resolvePath(string $path): string
    {
        // Normalize slashes
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        // Prevent traversing above root by stripping ..
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), function ($p) {
            return $p !== '' && $p !== '.' && $p !== '..';
        });
        
        $safePath = $this->rootPath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
        
        return $safePath;
    }

    private function isWithinRoot(string $fullPath): bool
    {
        return str_starts_with($fullPath, $this->rootPath);
    }

    public function listDirectory(string $path, bool $showHidden = true): array
    {
        $fullPath = $this->resolvePath($path);
        if (!is_dir($fullPath)) {
            throw new Exception("Directory not found: $path");
        }

        $items = scandir($fullPath);
        $result = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (!$showHidden && str_starts_with($item, '.')) {
                continue;
            }

            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            $relativePath = $path === '/' || $path === '' ? $item : $path . '/' . $item;
            
            $result[] = $this->getMetadataInternal($itemPath, $relativePath);
        }

        return $result;
    }

    public function readFile(string $path): string
    {
        $fullPath = $this->resolvePath($path);
        if (!is_file($fullPath)) {
            throw new Exception("File not found: $path");
        }
        return file_get_contents($fullPath);
    }

    public function writeFile(string $path, string $content): bool
    {
        $fullPath = $this->resolvePath($path);
        return file_put_contents($fullPath, $content) !== false;
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
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
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
            return $this->recurseCopy($fullFrom, $fullTo);
        } else {
            return copy($fullFrom, $fullTo);
        }
    }

    private function recurseCopy(string $src, string $dst): bool 
    { 
        $dir = opendir($src); 
        @mkdir($dst); 
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    $this->recurseCopy($src . '/' . $file,$dst . '/' . $file); 
                } 
                else { 
                    copy($src . '/' . $file,$dst . '/' . $file); 
                } 
            } 
        } 
        closedir($dir);
        return true; 
    }

    public function archive(array $sources, string $destination): bool
    {
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
                else $zip->addFile($fullSource, $baseName);
            }
            return $zip->close();
        } else if ($ext === 'tar' || $ext === 'tar.gz') {
            if (file_exists($fullDest)) unlink($fullDest);
            $archiveName = $ext === 'tar.gz' ? str_replace('.tar.gz', '.tar', $fullDest) : $fullDest;
            $phar = new PharData($archiveName);
            foreach ($sources as $source) {
                $fullSource = $this->resolvePath($source);
                if (is_dir($fullSource)) $phar->buildFromDirectory($fullSource);
                else $phar->addFile($fullSource, basename($fullSource));
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
        $zip->addEmptyDir($localPath);
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            $newLocalPath = $localPath . '/' . $file;
            if (is_dir($fullPath)) {
                $this->addDirToZip($zip, $fullPath, $newLocalPath);
            } else {
                $zip->addFile($fullPath, $newLocalPath);
            }
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
                $zip->extractTo($fullDest);
                $zip->close();
                return true;
            }
        } else if ($ext === 'tar' || $ext === 'tar.gz') {
            $phar = new PharData($fullArchive);
            return $phar->extractTo($fullDest, null, true);
        }

        throw new Exception("Failed to open or unsupported archive: $archive");
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
        $results = [];
        $dir = new \RecursiveDirectoryIterator($this->rootPath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if (stripos($file->getFilename(), $query) !== false) {
                // Calculate relative path
                $fullPath = $file->getPathname();
                // Ensure it's within root (redundant given Iterator start, but good practice)
                if (str_starts_with($fullPath, $this->rootPath)) {
                    $relativePath = substr($fullPath, strlen($this->rootPath) + 1);
                    // Fix windows slashes
                    $relativePath = str_replace('\\', '/', $relativePath);
                    
                    $results[] = $this->getMetadataInternal($fullPath, $relativePath);
                }
            }
        }
        return $results;
    }

    public function getDirectorySize(string $path): int
    {
        $fullPath = $this->resolvePath($path);
        if (!is_dir($fullPath)) return 0;

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    private function getMetadataInternal(string $fullPath, string $relativePath): array
    {
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
