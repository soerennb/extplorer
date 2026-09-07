<?php

namespace App\Services\VFS;

use RuntimeException;

/**
 * Explicitly denies filesystem access when no authenticated scope exists.
 */
final class DeniedFileSystem implements IFileSystem
{
    public function __construct(private readonly string $reason = 'Authentication required.')
    {
    }

    private function deny(): never
    {
        throw new RuntimeException($this->reason);
    }

    public function listDirectory(string $path, bool $showHidden = true): array
    {
        return $this->deny();
    }

    public function readFile(string $path): string
    {
        return $this->deny();
    }

    public function openReadStream(string $path)
    {
        return $this->deny();
    }

    public function writeFile(string $path, string $content): bool
    {
        return $this->deny();
    }

    public function delete(string $path): bool
    {
        return $this->deny();
    }

    public function createDirectory(string $path): bool
    {
        return $this->deny();
    }

    public function rename(string $from, string $to): bool
    {
        return $this->deny();
    }

    public function move(string $from, string $to): bool
    {
        return $this->deny();
    }

    public function copy(string $from, string $to): bool
    {
        return $this->deny();
    }

    public function getMetadata(string $path): ?array
    {
        return $this->deny();
    }

    public function chmod(string $path, int $mode, bool $recursive = false): bool
    {
        return $this->deny();
    }

    public function chown(string $path, $user, $group, bool $recursive = false): bool
    {
        return $this->deny();
    }

    public function getDirectorySize(string $path): int
    {
        return $this->deny();
    }

    public function archive(array $sources, string $destination): bool
    {
        return $this->deny();
    }

    public function extract(string $archive, string $destination): bool
    {
        return $this->deny();
    }

    public function search(string $query): array
    {
        return $this->deny();
    }
}
