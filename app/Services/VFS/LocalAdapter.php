<?php

namespace App\Services\VFS;

use CodeIgniter\Files\File;
use Exception;
use ZipArchive;

class LocalAdapter implements IFileSystem
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
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

    public function listDirectory(string $path): array
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
        $zip = new ZipArchive();
        $fullDest = $this->resolvePath($destination);

        if ($zip->open($fullDest, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot create zip file: $destination");
        }

        foreach ($sources as $source) {
            $fullSource = $this->resolvePath($source);
            $baseName = basename($fullSource);

            if (is_dir($fullSource)) {
                $this->addDirToZip($zip, $fullSource, $baseName);
            } else {
                $zip->addFile($fullSource, $baseName);
            }
        }

        return $zip->close();
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
        $zip = new ZipArchive();
        $fullArchive = $this->resolvePath($archive);
        $fullDest = $this->resolvePath($destination);

        if ($zip->open($fullArchive) === true) {
            $zip->extractTo($fullDest);
            $zip->close();
            return true;
        } else {
            throw new Exception("Failed to open archive: $archive");
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

    public function chmod(string $path, int $mode): bool
    {
        $fullPath = $this->resolvePath($path);
        if (!file_exists($fullPath)) {
            throw new Exception("File not found: $path");
        }
        return chmod($fullPath, $mode);
    }

    private function getMetadataInternal(string $fullPath, string $relativePath): array
    {
        $isDir = is_dir($fullPath);
        return [
            'name' => basename($fullPath),
            'path' => $relativePath,
            'type' => $isDir ? 'dir' : 'file',
            'size' => $isDir ? 0 : filesize($fullPath),
            'mtime' => filemtime($fullPath),
            'perms' => substr(sprintf('%o', fileperms($fullPath)), -4),
            'extension' => $isDir ? null : pathinfo($fullPath, PATHINFO_EXTENSION),
        ];
    }
}
