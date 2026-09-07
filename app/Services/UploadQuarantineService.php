<?php

namespace App\Services;

use RuntimeException;

/**
 * Holds uploaded content outside the served file tree until an external
 * scanner explicitly marks it clean. The scanner may be a native worker or a
 * separate container; it only needs to read pending manifests and call the
 * CLI result command.
 */
final class UploadQuarantineService
{
    public const MODE_OFF = 'off';
    public const MODE_EXTERNAL = 'external';

    /** @var list<string> */
    private const FINAL_STATUSES = ['clean', 'infected', 'error', 'expired'];

    private string $root;
    private string $lockPath;
    private ResourcePolicy $resourcePolicy;

    public function __construct(?string $root = null, ?ResourcePolicy $resourcePolicy = null)
    {
        $this->root = rtrim($root ?? config('Storage')->uploads . DIRECTORY_SEPARATOR . 'quarantine', '/\\');
        $this->lockPath = $this->root . DIRECTORY_SEPARATOR . '.quarantine.lock';
        $this->resourcePolicy = $resourcePolicy ?? new ResourcePolicy();
        $this->ensureRoot();
    }

    public function mode(): string
    {
        $mode = strtolower(trim((string)(getenv('EXTPLORER_UPLOAD_SCAN_MODE') ?: self::MODE_OFF)));
        if (!in_array($mode, [self::MODE_OFF, self::MODE_EXTERNAL], true)) {
            throw new RuntimeException('EXTPLORER_UPLOAD_SCAN_MODE must be off or external.');
        }

        return $mode;
    }

    public function enabled(): bool
    {
        return $this->mode() === self::MODE_EXTERNAL;
    }

    public function root(): string
    {
        return $this->root;
    }

    /** Return a private temporary path suitable for UploadedFile::move(). */
    public function incomingPath(): string
    {
        return $this->root . DIRECTORY_SEPARATOR . '.incoming-' . bin2hex(random_bytes(16));
    }

    /**
     * @param array<string, scalar|null> $metadata
     * @return array{id: string, status: string, size: int, payload_path: string}
     */
    public function stage(string $source, string $target, array $metadata = []): array
    {
        return $this->withLock(function () use ($source, $target, $metadata): array {
            $this->assertSource($source);
            $this->assertTarget($target);
            $size = (int)(filesize($source) ?: 0);
            $this->assertPendingBudget($size);

            $id = bin2hex(random_bytes(16));
            $payloadPath = $this->root . DIRECTORY_SEPARATOR . $id . '.payload';
            $manifestPath = $this->manifestPath($id);
            $temporary = $this->root . DIRECTORY_SEPARATOR . '.payload-' . bin2hex(random_bytes(16));
            try {
                $input = fopen($source, 'rb');
                $output = fopen($temporary, 'wb');
                if ($input === false || $output === false) {
                    throw new RuntimeException('Unable to open upload quarantine streams.');
                }
                try {
                    $copied = $this->resourcePolicy->copyStream($input, $output, $this->maxPendingBytes());
                } finally {
                    fclose($input);
                    fclose($output);
                }
                if ($copied !== $size || !chmod($temporary, 0600) || !rename($temporary, $payloadPath)) {
                    throw new RuntimeException('Unable to activate quarantined upload.');
                }

                $manifest = [
                    'id' => $id,
                    'status' => 'pending',
                    'owner' => (string)($metadata['owner'] ?? 'unknown'),
                    'target_path' => $target,
                    'filename' => (string)($metadata['filename'] ?? basename($target)),
                    'conflict' => (string)($metadata['conflict'] ?? 'replace'),
                    'size' => $size,
                    'sha256' => hash_file('sha256', $payloadPath),
                    'created_at' => time(),
                    'expires_at' => time() + $this->ttl(),
                    'metadata' => $this->safeMetadata($metadata),
                ];
                $this->writeManifest($manifestPath, $manifest);
                @unlink($source);

                return [
                    'id' => $id,
                    'status' => 'pending',
                    'size' => $size,
                    'payload_path' => $payloadPath,
                ];
            } catch (\Throwable $exception) {
                @unlink($temporary);
                @unlink($payloadPath);
                throw $exception;
            }
        });
    }

    /** @return array<string, mixed> */
    public function get(string $id): array
    {
        $this->assertId($id);
        $path = $this->manifestPath($id);
        if (!is_file($path)) {
            throw new RuntimeException('Upload quarantine item was not found.');
        }
        $raw = file_get_contents($path);
        $manifest = $raw === false ? null : json_decode($raw, true);
        if (!is_array($manifest) || ($manifest['id'] ?? '') !== $id) {
            throw new RuntimeException('Upload quarantine manifest is invalid.');
        }

        return $manifest;
    }

    /** @return list<array<string, mixed>> */
    public function pending(): array
    {
        $items = [];
        foreach (glob($this->root . DIRECTORY_SEPARATOR . '*.json') ?: [] as $path) {
            $id = basename($path, '.json');
            if (!preg_match('/\A[a-f0-9]{32}\z/', $id)) {
                continue;
            }
            try {
                $item = $this->get($id);
                if (($item['status'] ?? '') === 'pending') {
                    $item['payload_path'] = $this->payloadPath($id);
                    $items[] = $item;
                }
            } catch (\Throwable $exception) {
                log_message('error', 'Unable to inspect upload quarantine item: ' . $exception->getMessage());
            }
        }

        return $items;
    }

    /**
     * @return array{id: string, status: string, target_path?: string}
     */
    public function markResult(string $id, string $status, string $reason = ''): array
    {
        $status = strtolower(trim($status));
        if (!in_array($status, self::FINAL_STATUSES, true)) {
            throw new RuntimeException('Upload scan result must be clean, infected, error or expired.');
        }

        return $this->withLock(function () use ($id, $status, $reason): array {
            $manifest = $this->get($id);
            if (($manifest['status'] ?? '') !== 'pending') {
                throw new RuntimeException('Upload quarantine item has already been finalized.');
            }

            $payload = $this->payloadPath($id);
            if (!is_file($payload) || is_link($payload)) {
                throw new RuntimeException('Quarantined upload payload is missing.');
            }

            $manifest['status'] = $status;
            $manifest['result_reason'] = substr($reason, 0, 500);
            $manifest['result_at'] = time();
            if ($status === 'clean') {
                $target = (string)($manifest['target_path'] ?? '');
                $this->assertTarget($target);
                $this->promote($payload, $target, $id);
                $manifest['promoted_at'] = time();
            } else {
                @unlink($payload);
            }
            $this->writeManifest($this->manifestPath($id), $manifest);

            return [
                'id' => $id,
                'status' => $status,
                'target_path' => (string)($manifest['target_path'] ?? ''),
            ];
        });
    }

    public function cleanupExpired(): int
    {
        return $this->withLock(function (): int {
            $removed = 0;
            $now = time();
            foreach (glob($this->root . DIRECTORY_SEPARATOR . '*.json') ?: [] as $path) {
                $id = basename($path, '.json');
                if (!preg_match('/\A[a-f0-9]{32}\z/', $id)) {
                    continue;
                }
                try {
                    $manifest = $this->get($id);
                } catch (\Throwable $exception) {
                    continue;
                }
                if (($manifest['status'] ?? '') === 'pending' && (int)($manifest['expires_at'] ?? 0) <= $now) {
                    @unlink($this->payloadPath($id));
                    $manifest['status'] = 'expired';
                    $manifest['result_reason'] = 'Scan deadline expired.';
                    $manifest['result_at'] = $now;
                    $this->writeManifest($path, $manifest);
                    $removed++;
                }
            }

            // A failed move/assemble can leave a private temporary file behind
            // without a manifest. Remove only files older than the configured
            // quarantine TTL; active uploads remain untouched.
            $cutoff = $now - $this->ttl();
            foreach (['.incoming-*', '.payload-*', '.manifest-*', '*.payload'] as $pattern) {
                foreach (glob($this->root . DIRECTORY_SEPARATOR . $pattern) ?: [] as $temporary) {
                    if (!is_file($temporary) || is_link($temporary)) {
                        continue;
                    }
                    $mtime = filemtime($temporary);
                    if ($mtime !== false && $mtime <= $cutoff) {
                        @unlink($temporary);
                    }
                }
            }
            return $removed;
        });
    }

    private function promote(string $payload, string $target, string $id): void
    {
        if (is_link($target) || (file_exists($target) && !is_file($target))) {
            throw new RuntimeException('Upload target is not a regular file.');
        }

        $old = null;
        if (is_file($target)) {
            $old = $target . '.pre-scan-' . $id;
            if (!rename($target, $old)) {
                throw new RuntimeException('Unable to protect the previous upload target.');
            }
        }
        if (!rename($payload, $target)) {
            if ($old !== null) {
                @rename($old, $target);
            }
            throw new RuntimeException('Unable to activate scanned upload.');
        }
        if ($old !== null) {
            @unlink($old);
        }
    }

    private function assertSource(string $source): void
    {
        if (!is_file($source) || is_link($source)) {
            throw new RuntimeException('Upload source is not a regular file.');
        }
    }

    private function assertTarget(string $target): void
    {
        if ($target === '' || !str_starts_with($target, DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Upload target must be an absolute path.');
        }
        if (is_link($target)) {
            throw new RuntimeException('Upload target must not be a symbolic link.');
        }
        $parent = realpath(dirname($target));
        if ($parent === false || !$this->isAllowedTarget($parent)) {
            throw new RuntimeException('Upload target is outside the managed file roots.');
        }
    }

    private function isAllowedTarget(string $path): bool
    {
        $roots = [config('Storage')->fileManagerRoot, config('Storage')->uploads . DIRECTORY_SEPARATOR . 'shares'];
        $normalized = rtrim(str_replace('\\', '/', $path), '/');
        foreach ($roots as $root) {
            $root = realpath($root);
            if ($root === false) {
                continue;
            }
            $root = rtrim(str_replace('\\', '/', $root), '/');
            if ($normalized === $root || str_starts_with($normalized, $root . '/')) {
                return true;
            }
        }
        return false;
    }

    private function assertPendingBudget(int $additionalBytes): void
    {
        $pendingBytes = 0;
        $pendingCount = 0;
        foreach ($this->pending() as $item) {
            $pendingCount++;
            $pendingBytes += (int)($item['size'] ?? 0);
        }
        if ($pendingCount >= $this->maxPendingCount() || $pendingBytes + $additionalBytes > $this->maxPendingBytes()) {
            throw new RuntimeException('Upload quarantine capacity is exhausted.');
        }
    }

    private function maxPendingBytes(): int
    {
        return $this->resourcePolicy->maxConfiguredMegabytes('EXTPLORER_UPLOAD_QUARANTINE_MAX_MB', 2048, 1, 102400);
    }

    private function maxPendingCount(): int
    {
        return $this->resourcePolicy->configuredInteger('EXTPLORER_UPLOAD_QUARANTINE_MAX_FILES', 1000, 1, 100000);
    }

    private function ttl(): int
    {
        return $this->resourcePolicy->configuredInteger('EXTPLORER_UPLOAD_QUARANTINE_TTL_SECONDS', 86400, 60, 604800);
    }

    private function ensureRoot(): void
    {
        if (!is_dir($this->root) && !mkdir($this->root, 0700, true) && !is_dir($this->root)) {
            throw new RuntimeException('Unable to create upload quarantine storage.');
        }
        if (is_link($this->root) || !is_writable($this->root)) {
            throw new RuntimeException('Upload quarantine storage is not safe or writable.');
        }
        @chmod($this->root, 0700);
    }

    private function manifestPath(string $id): string
    {
        $this->assertId($id);
        return $this->root . DIRECTORY_SEPARATOR . $id . '.json';
    }

    private function payloadPath(string $id): string
    {
        $this->assertId($id);
        return $this->root . DIRECTORY_SEPARATOR . $id . '.payload';
    }

    /** @param array<string, mixed> $manifest */
    private function writeManifest(string $path, array $manifest): void
    {
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $temporary = tempnam($this->root, '.manifest-');
        if ($temporary === false || file_put_contents($temporary, $json, LOCK_EX) === false || !chmod($temporary, 0600) || !rename($temporary, $path)) {
            if (is_string($temporary)) {
                @unlink($temporary);
            }
            throw new RuntimeException('Unable to write upload quarantine manifest.');
        }
    }

    private function withLock(callable $callback): mixed
    {
        $lock = fopen($this->lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) fclose($lock);
            throw new RuntimeException('Unable to lock upload quarantine.');
        }
        try {
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function assertId(string $id): void
    {
        if (!preg_match('/\A[a-f0-9]{32}\z/', $id)) {
            throw new RuntimeException('Invalid upload quarantine ID.');
        }
    }

    /** @param array<string, scalar|null> $metadata */
    private function safeMetadata(array $metadata): array
    {
        $allowed = ['owner', 'filename', 'relative_path', 'share_hash', 'conflict', 'source'];
        $result = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $metadata)) {
                $result[$key] = is_scalar($metadata[$key]) || $metadata[$key] === null ? $metadata[$key] : (string)$metadata[$key];
            }
        }
        return $result;
    }
}
