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
    private StagingResourceService $stagingResources;

    public function __construct(
        ?string $root = null,
        ?string $signingKey = null,
        ?StagingResourceService $stagingResources = null
    )
    {
        $this->root = rtrim($root ?? config('Storage')->uploads . DIRECTORY_SEPARATOR . 'chunks', '/\\');
        $this->signingKey = $signingKey ?? (string)config('Encryption')->key;
        $this->stagingResources = $stagingResources ?? new StagingResourceService();

        if ($this->signingKey === '') {
            throw new RuntimeException('Upload session signing key is not configured.');
        }

        $this->assertNoSymlinkComponents($this->root);
        if (!is_dir($this->root) && !mkdir($this->root, 0700, true) && !is_dir($this->root)) {
            throw new RuntimeException('Unable to create upload session storage.');
        }
        $this->assertNoSymlinkComponents($this->root);
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
        if ($totalSize !== null) {
            $expectedChunks = max(1, (int)ceil($totalSize / $chunkSize));
            if ($totalChunks !== $expectedChunks) {
                throw new RuntimeException('Upload chunk count does not match the declared size.');
            }
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
            'expires_at' => $this->expirationTime(),
            'chunks' => [],
        ];

        try {
            $this->writeManifest($id, $manifest);
            $this->reserveManifest($manifest, $totalSize ?? 0);
        } catch (\Throwable $e) {
            $this->stagingResources->release($this->reservationId($id));
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
        if ($owner === '' || $filename === '') {
            throw new RuntimeException('Invalid upload session owner or filename.');
        }
        if ($chunkSize < self::MIN_CHUNK_SIZE || $chunkSize > self::MAX_CHUNK_SIZE) {
            throw new RuntimeException('Invalid upload chunk size.');
        }
        if ($totalChunks < 1 || $totalChunks > self::MAX_CHUNKS) {
            throw new RuntimeException('Invalid upload chunk count.');
        }
        if ($totalSize !== null) {
            if ($totalSize < 0) {
                throw new RuntimeException('Invalid upload size.');
            }
            $expectedChunks = max(1, (int)ceil($totalSize / $chunkSize));
            if ($totalChunks !== $expectedChunks) {
                throw new RuntimeException('Upload chunk count does not match the declared size.');
            }
        }

        $id = hash_hmac('sha256', $owner . '|' . $legacyKey, $this->signingKey);
        $directory = $this->directory($id);
        $manifestPath = $this->manifestPath($id);

        if (!AtomicFileStore::exists($manifestPath)) {
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
                'expires_at' => $this->expirationTime(),
                'chunks' => [],
            ];
            try {
                $this->writeManifest($id, $manifest);
                $this->reserveManifest($manifest, $totalSize ?? 0);
            } catch (\Throwable $e) {
                $this->stagingResources->release($this->reservationId($id));
                $this->removeDirectory($directory);
                throw $e;
            }
        }

        $manifest = $this->get($id);
        if (
            $manifest['owner'] !== $owner
            || $manifest['target_path'] !== $targetPath
            || $manifest['relative_path'] !== $relativePath
            || $manifest['filename'] !== $filename
            || (int)$manifest['total_chunks'] !== $totalChunks
            || ($manifest['total_size'] ?? null) !== $totalSize
        ) {
            throw new RuntimeException('Upload session metadata changed.');
        }

        $this->reserveManifest($manifest, array_sum(array_map('intval', $manifest['chunks'] ?? [])));

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
            // Do not mutate a session before its owner has been checked by the
            // caller. Cleanup is performed by cleanupExpired(), while this
            // read-only path must not let an unauthenticated request delete a
            // session merely by presenting its identifier.
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
                log_message('error', 'Invalid chunk index={index} count={count} bytes={bytes} chunk_size={chunk_size}', ['index' => $index, 'count' => $totalChunks, 'bytes' => $size, 'chunk_size' => $chunkSize]);
                throw new RuntimeException('Invalid upload chunk metadata.');
            }

            if (array_key_exists((string)$index, $manifest['chunks'] ?? [])) {
                throw new RuntimeException('Upload chunk was already stored.');
            }

            $path = $this->chunkPath($id, $index);
            if (!is_file($path) || is_link($path) || (int)filesize($path) !== $size) {
                throw new RuntimeException('Uploaded chunk is missing.');
            }

            $previousBytes = array_sum(array_map('intval', $manifest['chunks'] ?? []));
            $nextBytes = $previousBytes + $size;
            if ($manifest['total_size'] !== null && $nextBytes > (int)$manifest['total_size']) {
                throw new RuntimeException('Uploaded chunks exceed the declared size.');
            }
            try {
                $manifest['chunks'][(string)$index] = $size;
                $this->updateManifestReservation($manifest, $nextBytes);
                $this->writeManifest($id, $manifest);
            } catch (\Throwable $e) {
                unset($manifest['chunks'][(string)$index]);
                try {
                    $this->reserveManifest($manifest, $previousBytes);
                } catch (\Throwable $rollbackException) {
                    log_message('error', 'Unable to roll back upload staging reservation: ' . $rollbackException->getMessage());
                }
                @unlink($path);
                throw $e;
            }
        });
    }

    public function storeChunkFromPath(string $id, int $index, string $source, int $size): void
    {
        $this->withLock($id, function () use ($id, $index, $source, $size): void {
            $manifest = $this->get($id);
            $totalChunks = (int)($manifest['total_chunks'] ?? 0);
            $chunkSize = (int)($manifest['chunk_size'] ?? 0);
            if ($index < 0 || $index >= $totalChunks || $size < 0 || $size > $chunkSize) {
                log_message('error', 'Invalid chunk index={index} count={count} bytes={bytes} chunk_size={chunk_size}', ['index' => $index, 'count' => $totalChunks, 'bytes' => $size, 'chunk_size' => $chunkSize]);
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
                $previousBytes = array_sum(array_map('intval', $manifest['chunks'] ?? []));
                $nextBytes = $previousBytes + $size;
                if ($manifest['total_size'] !== null && $nextBytes > (int)$manifest['total_size']) {
                    throw new RuntimeException('Uploaded chunks exceed the declared size.');
                }
                $manifest['chunks'][(string)$index] = $size;
                $this->updateManifestReservation($manifest, $nextBytes);
                $this->writeManifest($id, $manifest);
            } catch (\Throwable $e) {
                @unlink($destination);
                $this->reserveManifest($manifest, $previousBytes ?? 0);
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

    public function bindTarget(string $id, \App\Services\VFS\UploadTarget $target): void
    {
        $this->withLock($id, function () use ($id, $target): void {
            $manifest = $this->get($id);
            $this->assertOwner($manifest, $target->owner);
            if (isset($manifest['target'])) throw new RuntimeException('Upload target is already bound.');
            $manifest['target'] = $target->toArray();
            $this->writeManifest($id, $manifest);
        });
    }

    /** The callback accepts a complete private payload; consume chunks only after acceptance. */
    public function finalize(string $id, callable $callback): array
    {
        return $this->withLock($id, function () use ($id, $callback): array {
            $manifest = $this->get($id);
            $missing = $this->missingChunks($manifest);
            if ($missing !== []) throw new RuntimeException('Upload is incomplete. Missing chunks: ' . implode(',', $missing));
            $temporary = tempnam($this->root, '.assembled-' . $id . '-');
            if ($temporary === false) throw new RuntimeException('Unable to create assembled payload.');
            $output = null;
            try {
                $output = fopen($temporary, 'wb');
                if ($output === false) throw new RuntimeException('Unable to open assembled payload.');
                $bytes = 0;
                $policy = new \App\Services\ResourcePolicy();
                $budget = $policy->startOperation();
                for ($index = 0; $index < (int)$manifest['total_chunks']; $index++) {
                    $budget->tick();
                    $path = $this->chunkPath($id, $index);
                    $this->assertNoSymlinkComponents($path);
                    $input = fopen($path, 'rb');
                    if ($input === false) throw new RuntimeException('Unable to read upload chunk.');
                    try {
                        $expected = (int)$manifest['chunks'][(string)$index];
                        $copied = $policy->copyStream($input, $output, max(1, $expected));
                        if ($copied !== $expected) throw new RuntimeException('Upload chunk changed during assembly.');
                        $bytes += $copied;
                    } finally { fclose($input); }
                }
                fclose($output);
                $output = null;
                if ($manifest['total_size'] !== null && (int)$manifest['total_size'] !== $bytes) throw new RuntimeException('Assembled upload size does not match the declared size.');
                $result = $callback($manifest, $temporary, $bytes);
                $this->removeDirectory($this->directory($id));
                $this->stagingResources->release($this->reservationId($id));
                return $result;
            } finally {
                if (is_resource($output)) fclose($output);
                if (is_file($temporary)) unlink($temporary);
            }
        });
    }

    public function assemble(string $id, string $destination): int
    {
        $result = $this->finalize($id, function (array $manifest, string $payload, int $bytes) use ($destination): array {
            $this->assertSafeDestinationDirectory(dirname($destination));
            if (is_link($destination) || !rename($payload, $destination)) throw new RuntimeException('Unable to activate assembled upload.');
            return ['bytes' => $bytes];
        });
        return $result['bytes'];
    }

    public function abort(string $id): void
    {
        $this->withLock($id, function () use ($id): void {
            $manifest = AtomicFileStore::read($this->manifestPath($id));
            if (isset($manifest['target'])) (new UploadTargetResolver())->release(\App\Services\VFS\UploadTarget::fromArray($manifest['target']));
            $this->removeDirectory($this->directory($id));
            $this->stagingResources->release($this->reservationId($id), $manifest['owner'] ?? null);
        });
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
                if (!AtomicFileStore::exists($manifestPath)) {
                    $this->removeDirectory($this->directory($id));
                    $this->stagingResources->release($this->reservationId($id));
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

                if (isset($manifest['target'])) (new UploadTargetResolver())->release(\App\Services\VFS\UploadTarget::fromArray($manifest['target']));
                $this->removeDirectory($this->directory($id));
                $this->stagingResources->release(
                    $this->reservationId($id),
                    is_string($manifest['owner'] ?? null) ? $manifest['owner'] : null
                );
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
        $this->assertNoSymlinkComponents($this->root);
        if (is_link($this->directory($id))) {
            throw new RuntimeException('Upload session storage uses a symbolic link.');
        }
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
            // Keep the lock inode: unlinking can let a third process acquire
            // a different lock while a waiting process still owns this one.
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
        AtomicFileStore::delete($directory . DIRECTORY_SEPARATOR . 'session.php');
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

    private function assertSafeDestinationDirectory(string $directory): void
    {
        if (!is_dir($directory) || is_link($directory) || realpath($directory) !== $directory) {
            throw new RuntimeException('Upload destination does not exist or uses a symbolic link.');
        }

        $current = $directory;
        while ($current !== dirname($current)) {
            if (is_link($current)) {
                throw new RuntimeException('Upload destination uses a symbolic link.');
            }
            $current = dirname($current);
        }
    }

    private function assertNoSymlinkComponents(string $path): void
    {
        $current = rtrim($path, '/\\');
        while ($current !== dirname($current)) {
            if (is_link($current)) {
                throw new RuntimeException('Upload session storage uses a symbolic link.');
            }
            $current = dirname($current);
        }
    }

    /** @param array<string, mixed> $manifest */
    private function reserveManifest(array $manifest, int $bytes): void
    {
        $this->stagingResources->reserve(
            (string)$manifest['owner'],
            $this->reservationId((string)$manifest['id']),
            $bytes,
            1,
            ['kind' => 'upload', 'session_id' => (string)$manifest['id']],
            (int)$manifest['expires_at']
        );
    }

    /** @param array<string, mixed> $manifest */
    private function updateManifestReservation(array $manifest, int $bytes): void
    {
        $this->reserveManifest($manifest, $bytes);
    }

    private function reservationId(string $id): string
    {
        return 'upload:' . $id;
    }

    private function expirationTime(): int
    {
        return time() + (new ResourcePolicy())->uploadStagingTtlSeconds();
    }
}
