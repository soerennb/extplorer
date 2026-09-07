<?php

namespace App\Services;

use Closure;
use RuntimeException;

/**
 * Owns resumable upload state and makes completion an atomic operation.
 */
final class UploadSessionService
{
    public const DEFAULT_TTL = 86_400;
    public const MIN_CHUNK_SIZE = 64 * 1024;
    public const MAX_CHUNK_SIZE = 8 * 1024 * 1024;
    public const MAX_CHUNKS = 100_000;

    private string $root;
    private string $signingKey;

    public function __construct(?string $root = null, ?string $signingKey = null)
    {
        $this->root = rtrim($root ?? config('Storage')->uploads . DIRECTORY_SEPARATOR . 'chunks', '/\\');
        $this->signingKey = $signingKey ?? (string)config('Encryption')->key;

        if ($this->signingKey === '') {
            throw new RuntimeException('Upload session signing key is not configured.');
        }

        if (!is_dir($this->root) && !mkdir($this->root, 0700, true) && !is_dir($this->root)) {
            throw new RuntimeException('Unable to create upload session storage.');
        }
    }

    /**
     * @return array{id: string, expires_at: int, total_chunks: int}
     */
    public function create(
        string $owner,
        string $targetPath,
        string $relativePath,
        string $filename,
        ?int $totalSize,
        int $chunkSize,
        ?int $totalChunks = null,
        string $conflict = 'replace'
    ): array {
        if ($owner === '' || $filename === '') {
            throw new RuntimeException('Invalid upload session owner or filename.');
        }
        if ($chunkSize < self::MIN_CHUNK_SIZE || $chunkSize > self::MAX_CHUNK_SIZE) {
            throw new RuntimeException('Invalid upload chunk size.');
        }
        if ($totalSize !== null && $totalSize < 0) {
            throw new RuntimeException('Invalid upload size.');
        }

        if ($totalChunks === null) {
            $totalChunks = max(1, (int)ceil(($totalSize ?? 0) / $chunkSize));
        }
        if ($totalChunks < 1 || $totalChunks > self::MAX_CHUNKS) {
            throw new RuntimeException('Invalid upload chunk count.');
        }

        do {
            $id = bin2hex(random_bytes(16));
            $directory = $this->directory($id);
        } while (file_exists($directory));

        if (!mkdir($directory, 0700, true)) {
            throw new RuntimeException('Unable to create upload session.');
        }

        $manifest = [
            'id' => $id,
            'owner' => $owner,
            'target_path' => $targetPath,
            'relative_path' => $relativePath,
            'filename' => $filename,
            'total_size' => $totalSize,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'conflict' => $conflict,
            'created_at' => time(),
            'expires_at' => time() + self::DEFAULT_TTL,
            'chunks' => [],
        ];

        try {
            $this->writeManifest($id, $manifest);
        } catch (\Throwable $e) {
            $this->removeDirectory($directory);
            throw $e;
        }

        return [
            'id' => $id,
            'expires_at' => $manifest['expires_at'],
            'total_chunks' => $totalChunks,
        ];
    }

    /**
     * Returns a deterministic compatibility session for the legacy endpoint.
     * The actual manifest remains server-owned and is bound to the current user.
     *
     * @return array{id: string, expires_at: int, total_chunks: int}
     */
    public function legacy(
        string $owner,
        string $legacyKey,
        string $targetPath,
        string $relativePath,
        string $filename,
        int $totalChunks,
        ?int $totalSize,
        int $chunkSize,
        string $conflict
    ): array {
        $id = hash_hmac('sha256', $owner . '|' . $legacyKey, $this->signingKey);
        $directory = $this->directory($id);
        $manifestPath = $this->manifestPath($id);

        if (!is_file($manifestPath)) {
            if (!mkdir($directory, 0700, true)) {
                throw new RuntimeException('Unable to create upload session.');
            }
            $manifest = [
                'id' => $id,
                'owner' => $owner,
                'target_path' => $targetPath,
                'relative_path' => $relativePath,
                'filename' => $filename,
                'total_size' => $totalSize,
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
                'conflict' => $conflict,
                'created_at' => time(),
                'expires_at' => time() + self::DEFAULT_TTL,
                'chunks' => [],
            ];
            $this->writeManifest($id, $manifest);
        }

        $manifest = $this->get($id);
        if (
            $manifest['owner'] !== $owner
            || $manifest['target_path'] !== $targetPath
            || $manifest['relative_path'] !== $relativePath
            || $manifest['filename'] !== $filename
            || (int)$manifest['total_chunks'] !== $totalChunks
        ) {
            throw new RuntimeException('Upload session metadata changed.');
        }

        return [
            'id' => $id,
            'expires_at' => (int)$manifest['expires_at'],
            'total_chunks' => (int)$manifest['total_chunks'],
        ];
    }

    /** @return array<string, mixed> */
    public function get(string $id): array
    {
        $this->assertValidId($id);
        $manifest = AtomicFileStore::read($this->manifestPath($id));
        if (($manifest['id'] ?? '') !== $id) {
            throw new RuntimeException('Invalid upload session.');
        }

        if ((int)($manifest['expires_at'] ?? 0) <= time()) {
            $this->abort($id);
            throw new RuntimeException('Upload session expired.');
        }

        return $manifest;
    }

    public function assertOwner(array $manifest, string $owner): void
    {
        if (($manifest['owner'] ?? '') !== $owner) {
            throw new RuntimeException('Upload session does not belong to this user.');
        }
    }

    public function chunkPath(string $id, int $index): string
    {
        $this->assertValidId($id);
        if ($index < 0 || $index >= self::MAX_CHUNKS) {
            throw new RuntimeException('Invalid upload chunk index.');
        }

        return $this->directory($id) . DIRECTORY_SEPARATOR . 'chunk-' . $index . '.part';
    }

    public function stagingPath(string $id): string
    {
        $this->assertValidId($id);
        $directory = $this->directory($id);
        if (!is_dir($directory) || is_link($directory)) {
            throw new RuntimeException('Upload session does not exist.');
        }

        return $directory . DIRECTORY_SEPARATOR . 'incoming-' . bin2hex(random_bytes(16)) . '.part';
    }

    public function storeChunk(string $id, int $index, int $size): void
    {
        $this->withLock($id, function () use ($id, $index, $size): void {
            $manifest = $this->get($id);
            $totalChunks = (int)($manifest['total_chunks'] ?? 0);
            $chunkSize = (int)($manifest['chunk_size'] ?? 0);
            if ($index < 0 || $index >= $totalChunks || $size < 0 || $size > $chunkSize) {
                throw new RuntimeException('Invalid upload chunk metadata.');
            }

            if (array_key_exists((string)$index, $manifest['chunks'] ?? [])) {
                throw new RuntimeException('Upload chunk was already stored.');
            }

            $path = $this->chunkPath($id, $index);
            if (!is_file($path) || is_link($path) || (int)filesize($path) !== $size) {
                throw new RuntimeException('Uploaded chunk is missing.');
            }

            $manifest['chunks'][(string)$index] = $size;
            $this->writeManifest($id, $manifest);
        });
    }

    public function storeChunkFromPath(string $id, int $index, string $source, int $size): void
    {
        $this->withLock($id, function () use ($id, $index, $source, $size): void {
            $manifest = $this->get($id);
            $totalChunks = (int)($manifest['total_chunks'] ?? 0);
            $chunkSize = (int)($manifest['chunk_size'] ?? 0);
            if ($index < 0 || $index >= $totalChunks || $size < 0 || $size > $chunkSize) {
                throw new RuntimeException('Invalid upload chunk metadata.');
            }
            if (array_key_exists((string)$index, $manifest['chunks'] ?? [])) {
                throw new RuntimeException('Upload chunk was already stored.');
            }
            if (!is_file($source) || is_link($source) || (int)filesize($source) !== $size) {
                throw new RuntimeException('Uploaded chunk is missing.');
            }

            $destination = $this->chunkPath($id, $index);
            if (file_exists($destination) || is_link($destination)) {
                throw new RuntimeException('Upload chunk was already stored.');
            }
            if (!rename($source, $destination)) {
                throw new RuntimeException('Unable to store upload chunk.');
            }

            try {
                $manifest['chunks'][(string)$index] = $size;
                $this->writeManifest($id, $manifest);
            } catch (\Throwable $e) {
                @unlink($destination);
                throw $e;
            }
        });
    }

    /** @return list<int> */
    public function missingChunks(array $manifest): array
    {
        $missing = [];
        $chunks = is_array($manifest['chunks'] ?? null) ? $manifest['chunks'] : [];
        $totalChunks = (int)($manifest['total_chunks'] ?? 0);
        for ($index = 0; $index < $totalChunks; $index++) {
            $path = $this->chunkPath((string)$manifest['id'], $index);
            if (!array_key_exists((string)$index, $chunks) || !is_file($path)) {
                $missing[] = $index;
            }
        }

        return $missing;
    }

    public function assemble(string $id, string $destination): int
    {
        return $this->withLock($id, function () use ($id, $destination): int {
            $manifest = $this->get($id);
            $missing = $this->missingChunks($manifest);
            if ($missing !== []) {
                throw new RuntimeException('Upload is incomplete. Missing chunks: ' . implode(',', $missing));
            }

            $expectedSize = $manifest['total_size'];
            $actualSize = 0;
            $directory = dirname($destination);
            if (!is_dir($directory)) {
                throw new RuntimeException('Upload destination does not exist.');
            }

            $temporary = tempnam($directory, '.extplorer-upload-');
            if ($temporary === false) {
                throw new RuntimeException('Unable to create final upload temporary file.');
            }

            try {
                $output = fopen($temporary, 'wb');
                if ($output === false) {
                    throw new RuntimeException('Unable to open final upload temporary file.');
                }

                $totalChunks = (int)$manifest['total_chunks'];
                for ($index = 0; $index < $totalChunks; $index++) {
                    $chunkPath = $this->chunkPath($id, $index);
                    $input = fopen($chunkPath, 'rb');
                    if ($input === false) {
                        fclose($output);
                        throw new RuntimeException('Unable to read upload chunk.');
                    }

                    $copied = stream_copy_to_stream($input, $output);
                    fclose($input);
                    if ($copied === false) {
                        fclose($output);
                        throw new RuntimeException('Unable to assemble upload.');
                    }
                    $actualSize += $copied;
                }

                if (!fclose($output)) {
                    throw new RuntimeException('Unable to finalize upload.');
                }

                if ($expectedSize !== null && (int)$expectedSize !== $actualSize) {
                    throw new RuntimeException('Assembled upload size does not match the declared size.');
                }

                if (!rename($temporary, $destination)) {
                    throw new RuntimeException('Unable to activate assembled upload.');
                }

                $this->removeDirectory($this->directory($id));
                return $actualSize;
            } finally {
                if (is_file($temporary)) {
                    @unlink($temporary);
                }
            }
        });
    }

    public function abort(string $id): void
    {
        $this->assertValidId($id);
        $directory = $this->directory($id);
        if (is_dir($directory)) {
            $this->removeDirectory($directory);
        }
    }

    public function cleanupExpired(): int
    {
        $removed = 0;
        foreach (scandir($this->root) ?: [] as $id) {
            if (!preg_match('/\A[a-f0-9]{32,64}\z/', $id)) {
                continue;
            }

            $directory = $this->directory($id);
            if (!is_dir($directory) || is_link($directory)) {
                continue;
            }

            $wasRemoved = $this->withLock($id, function () use ($id): bool {
                $manifestPath = $this->manifestPath($id);
                if (!is_file($manifestPath)) {
                    $this->removeDirectory($this->directory($id));
                    return true;
                }

                try {
                    $manifest = AtomicFileStore::read($manifestPath);
                } catch (\Throwable $exception) {
                    log_message('error', 'Unable to inspect upload session during cleanup: ' . $exception->getMessage());
                    return false;
                }

                if ((int)($manifest['expires_at'] ?? 0) > time()) {
                    return false;
                }

                $this->removeDirectory($this->directory($id));
                return true;
            });

            if ($wasRemoved) {
                $removed++;
            }
        }

        return $removed;
    }

    private function withLock(string $id, Closure $callback): mixed
    {
        $this->assertValidId($id);
        $lockPath = $this->root . DIRECTORY_SEPARATOR . '.lock-' . $id;
        $lock = @fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new RuntimeException('Unable to lock upload session.');
        }

        try {
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($lockPath);
        }
    }

    /** @param array<string, mixed> $manifest */
    private function writeManifest(string $id, array $manifest): void
    {
        AtomicFileStore::write($this->manifestPath($id), $manifest);
    }

    private function manifestPath(string $id): string
    {
        $this->assertValidId($id);
        return $this->directory($id) . DIRECTORY_SEPARATOR . 'session.php';
    }

    private function directory(string $id): string
    {
        $this->assertValidId($id);
        return $this->root . DIRECTORY_SEPARATOR . $id;
    }

    private function assertValidId(string $id): void
    {
        if (!preg_match('/\A[a-f0-9]{32,64}\z/', $id)) {
            throw new RuntimeException('Invalid upload session ID.');
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory) || is_link($directory)) {
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
}
