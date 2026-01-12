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
        $this->versionRoot = WRITEPATH . 'versions' . DIRECTORY_SEPARATOR . md5($username);

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
        $backupPath = $versionDir . DIRECTORY_SEPARATOR . $timestamp . '.bak';

        copy($fullPath, $backupPath);
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
            $path = $versionDir . DIRECTORY_SEPARATOR . $file;
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
        $backupPath = $versionDir . DIRECTORY_SEPARATOR . $versionId;

        if (!file_exists($backupPath)) {
            throw new Exception("Version not found.");
        }

        $targetPath = $fs->resolvePath($relativePath);
        
        // Before restoring, create a version of the CURRENT state so we can undo the restore
        $this->createVersion($targetPath, $relativePath);

        if (!copy($backupPath, $targetPath)) {
            throw new Exception("Failed to restore file.");
        }
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
