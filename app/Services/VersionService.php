<?php

namespace App\Services;

use Exception;

class VersionService
{
    private string $versionRoot;
    private string $username;
    private int $maxVersions = 10;

    public function __construct(string $username)
    {
        $this->username = $username;
        $this->versionRoot = config('Storage')->versions . DIRECTORY_SEPARATOR . md5($username);

        if (!is_dir($this->versionRoot)) {
            mkdir($this->versionRoot, 0755, true);
        }
    }

    private function getPathHash(string $relativePath): string
    {
        return md5($relativePath);
    }

    private function getVersionDir(string $relativePath): string
    {
        $dir = $this->versionRoot . DIRECTORY_SEPARATOR . $this->getPathHash($relativePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Creates a backup of the file if it exists.
     */
    public function createVersion(string $fullPath, string $relativePath): void
    {
        if (!file_exists($fullPath) || is_dir($fullPath)) {
            return;
        }

        $versionDir = $this->getVersionDir($relativePath);
        $timestamp = time();
        $backupPath = $versionDir . DIRECTORY_SEPARATOR . $timestamp . '_' . bin2hex(random_bytes(6)) . '.bak';

        if (!copy($fullPath, $backupPath)) {
            throw new Exception('Failed to create file version.');
        }
        $this->cleanup($relativePath);
    }

    /**
     * Lists all available versions for a file.
     */
    public function listVersions(string $relativePath): array
    {
        $versionDir = $this->versionRoot . DIRECTORY_SEPARATOR . $this->getPathHash($relativePath);
        if (!is_dir($versionDir)) {
            return [];
        }

        $files = array_diff(scandir($versionDir), ['.', '..']);
        $versions = [];

        foreach ($files as $file) {
            if (!$this->isSafeVersionId($file)) {
                continue;
            }
            $path = $versionDir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path) || is_link($path)) {
                continue;
            }
            $timestamp = (int)pathinfo($file, PATHINFO_FILENAME);
            $versions[] = [
                'id' => $file,
                'timestamp' => $timestamp,
                'date' => date('Y-m-d H:i:s', $timestamp),
                'size' => filesize($path)
            ];
        }

        // Sort newest first
        usort($versions, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $versions;
    }

    /**
     * Restores a specific version.
     */
    public function restoreVersion(string $relativePath, string $versionId, \App\Services\VFS\IFileSystem $fs): void
    {
        $versionDir = $this->versionRoot . DIRECTORY_SEPARATOR . $this->getPathHash($relativePath);
        if (!$this->isSafeVersionId($versionId)) {
            throw new Exception('Version not found.');
        }
        $backupPath = $versionDir . DIRECTORY_SEPARATOR . $versionId;

        $versionRoot = realpath($versionDir);
        $backupRealPath = realpath($backupPath);
        if ($versionRoot === false || $backupRealPath === false || !is_file($backupRealPath) || is_link($backupPath)
            || !$this->isWithinDirectory($versionRoot, $backupRealPath)) {
            throw new Exception("Version not found.");
        }

        $targetPath = $fs->resolvePath($relativePath);
        
        // Before restoring, create a version of the CURRENT state so we can undo the restore
        $this->createVersion($targetPath, $relativePath);

        $temporary = tempnam(dirname($targetPath), '.extplorer-restore-');
        if ($temporary === false) {
            throw new Exception("Failed to create restore staging file.");
        }

        try {
            if (!copy($backupRealPath, $temporary) || !rename($temporary, $targetPath)) {
                throw new Exception("Failed to restore file.");
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function isSafeVersionId(string $versionId): bool
    {
        return preg_match('/\A\d{10,}(?:_[a-f0-9]{12})?\.bak\z/i', $versionId) === 1
            && basename($versionId) === $versionId;
    }

    private function isWithinDirectory(string $directory, string $path): bool
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        return $path === $directory || str_starts_with($path, $directory . DIRECTORY_SEPARATOR);
    }

    /**
     * Keeps only the last X versions.
     */
    private function cleanup(string $relativePath): void
    {
        $versions = $this->listVersions($relativePath);
        if (count($versions) > $this->maxVersions) {
            $toDelete = array_slice($versions, $this->maxVersions);
            $versionDir = $this->versionRoot . DIRECTORY_SEPARATOR . $this->getPathHash($relativePath);
            foreach ($toDelete as $v) {
                @unlink($versionDir . DIRECTORY_SEPARATOR . $v['id']);
            }
        }
    }
}
