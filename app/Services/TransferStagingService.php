<?php

namespace App\Services;

/** Cleans transfer staging directories that were abandoned by a client. */
final class TransferStagingService
{
    private string $root;
    private StagingResourceService $resources;
    private ResourcePolicy $policy;

    public function __construct(
        ?string $root = null,
        ?StagingResourceService $resources = null,
        ?ResourcePolicy $policy = null
    ) {
        $this->root = rtrim($root ?? config('Storage')->uploads . DIRECTORY_SEPARATOR . 'temp', '/\\');
        $this->resources = $resources ?? new StagingResourceService();
        $this->policy = $policy ?? new ResourcePolicy();
    }

    public function cleanupExpired(): int
    {
        $this->assertNoSymlinkComponents($this->root);
        $removed = 0;
        foreach ($this->resources->cleanupExpired() as $record) {
            if (($record['metadata']['kind'] ?? '') !== 'transfer') {
                continue;
            }
            $directory = (string)($record['metadata']['directory'] ?? '');
            if ($directory !== '' && $this->isWithinRoot($directory)) {
                $this->removeDirectory($directory);
                $removed++;
            }
        }

        if (!is_dir($this->root) || is_link($this->root)) {
            return $removed;
        }

        $cutoff = time() - $this->policy->uploadStagingTtlSeconds();
        foreach (scandir($this->root) ?: [] as $userDirectory) {
            if ($userDirectory === '.' || $userDirectory === '..') {
                continue;
            }
            $userPath = $this->root . DIRECTORY_SEPARATOR . $userDirectory;
            if (!is_dir($userPath) || is_link($userPath)) {
                continue;
            }
            foreach (scandir($userPath) ?: [] as $sessionDirectory) {
                if ($sessionDirectory === '.' || $sessionDirectory === '..' || str_starts_with($sessionDirectory, '.session-')) {
                    continue;
                }
                $sessionPath = $userPath . DIRECTORY_SEPARATOR . $sessionDirectory;
                if (!is_dir($sessionPath) || is_link($sessionPath)) {
                    continue;
                }
                $mtime = filemtime($sessionPath);
                if ($mtime !== false && $mtime <= $cutoff) {
                    $this->removeDirectory($sessionPath);
                    $removed++;
                }
            }
        }

        return $removed;
    }

    private function isWithinRoot(string $path): bool
    {
        $root = realpath($this->root);
        $candidate = realpath($path);
        if ($root === false || $candidate === false || is_link($path)) {
            return false;
        }

        $root = rtrim(str_replace('\\', '/', $root), '/');
        $candidate = rtrim(str_replace('\\', '/', $candidate), '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            $root = strtolower($root);
            $candidate = strtolower($candidate);
        }
        if ($candidate === $root) {
            return false;
        }
        return str_starts_with($candidate . '/', $root . '/');
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory) || is_link($directory) || !$this->isWithinRoot($directory)) {
            return;
        }
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            if (is_link($path) || is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }
        @rmdir($directory);
    }

    private function assertNoSymlinkComponents(string $path): void
    {
        $current = rtrim($path, '/\\');
        while ($current !== dirname($current)) {
            if (is_link($current)) {
                throw new \RuntimeException('Transfer staging storage uses a symbolic link.');
            }
            $current = dirname($current);
        }
    }
}
