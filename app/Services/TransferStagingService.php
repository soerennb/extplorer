<?php

namespace App\Services;

use App\Services\VFS\IFileSystem;
use App\Services\VFS\PathPolicy;
use RuntimeException;

/** Owns transfer payloads, session locks, resource reservations and expiration. */
final class TransferStagingService
{
    private string $root;
    private StagingResourceService $resources;
    private ResourcePolicy $policy;

    public function __construct(?string $root = null, ?StagingResourceService $resources = null, ?ResourcePolicy $policy = null)
    {
        $this->root = rtrim($root ?? config('Storage')->uploads . '/temp', '/\\');
        $this->resources = $resources ?? new StagingResourceService();
        $this->policy = $policy ?? new ResourcePolicy();
    }

    public function directory(string $owner, string $id): string
    {
        if ($owner === '' || preg_match('/\A[A-Za-z0-9]{16,64}\z/', $id) !== 1) throw new RuntimeException('Invalid transfer session.');
        $directory = $this->root . '/' . hash('sha256', $owner) . '/' . $id;
        PrivateFileTree::assertNoSymlinks($directory);
        return $directory;
    }

    public function withSession(string $owner, string $id, callable $callback): mixed
    {
        $directory = $this->directory($owner, $id);
        return OperationLock::run(dirname($directory) . '/.session-' . $id . '.lock', function () use ($directory, $callback): mixed {
            PrivateFileTree::assertNoSymlinks($directory);
            return $callback($directory);
        });
    }

    public function status(string $owner, string $id, string $filename): array
    {
        $filename = (new TransferPolicy())->filename($filename);
        return $this->withSession($owner, $id, function (string $directory) use ($filename): array {
            $final = $directory . '/' . $filename;
            $partial = $final . '.part';
            PrivateFileTree::assertNoSymlinks($partial);
            PrivateFileTree::assertNoSymlinks($final);
            clearstatcache();
            if (is_file($final)) return ['status' => 'complete', 'uploaded' => (int)filesize($final)];
            if (is_file($partial)) return ['status' => 'partial', 'uploaded' => (int)filesize($partial)];
            return ['status' => 'new', 'uploaded' => 0];
        });
    }

    public function stage(string $owner, string $id, IFileSystem $fs, array $paths, int $maxBytes): array
    {
        return $this->withSession($owner, $id, function (string $directory) use ($owner, $id, $fs, $paths, $maxBytes): array {
            $this->ensureDirectory($directory);
            $count = $bytes = 0;
            foreach ($paths as $path) {
                if (!is_string($path)) throw new RuntimeException('Invalid transfer source path.');
                $meta = $fs->getMetadata($path);
                if (!$meta || ($meta['type'] ?? '') === 'dir') continue;
                $name = (new TransferPolicy())->filename($path);
                $destination = $directory . '/' . $name;
                PrivateFileTree::assertNoSymlinks($destination);
                $size = (int)($meta['size'] ?? 0);
                if ($maxBytes > 0 && $size > $maxBytes) throw new RuntimeException('File exceeds the configured size limit.');
                $this->reserveReplacement($owner, $id, $directory, $destination, $size);
                $temporary = tempnam($directory, '.extplorer-stage-');
                if ($temporary === false) throw new RuntimeException('Unable to create transfer staging file.', 500);
                $input = $output = null;
                try {
                    $input = $fs->openReadStream($path);
                    $output = fopen($temporary, 'wb');
                    $copied = $this->policy->copyStream($input, $output, $maxBytes > 0 ? $maxBytes : null);
                    if ($size > 0 && $copied !== $size) throw new RuntimeException('Transfer source changed while it was being staged.');
                    fclose($output);
                    $output = null;
                    $this->reserveReplacement($owner, $id, $directory, $destination, $copied, $temporary);
                    if (!rename($temporary, $destination)) throw new RuntimeException('Unable to activate transfer staging file.', 500);
                    $count++;
                    $bytes += $copied;
                } finally {
                    if (is_resource($input)) fclose($input);
                    if (is_resource($output)) fclose($output);
                    if (is_file($temporary)) unlink($temporary);
                    $this->sync($owner, $id, $directory);
                }
            }
            return ['status' => 'success', 'count' => $count, 'bytes' => $bytes];
        });
    }

    public function upload(string $owner, string $id, string $filename, string $source, int $offset, int $size, int $index, int $chunks, int $maxBytes): array
    {
        $filename = (new TransferPolicy())->filename($filename);
        $chunkBytes = is_file($source) ? (int)filesize($source) : 0;
        if ($size < 1 || $offset < 0 || $chunks < 1 || $chunks > 100000 || $index < 0 || $index >= $chunks
            || $chunkBytes < 1 || $chunkBytes > 8 * 1024 * 1024 || $offset > $size || $chunkBytes > $size - $offset) throw new RuntimeException('Invalid transfer chunk metadata.');
        if ($maxBytes > 0 && $size > $maxBytes) throw new RuntimeException('File exceeds the configured upload size limit.');
        return $this->withSession($owner, $id, function (string $directory) use ($owner, $id, $filename, $source, $offset, $size, $index, $chunks, $chunkBytes): array {
            $this->ensureDirectory($directory);
            $partial = $directory . '/' . $filename . '.part';
            $destination = $directory . '/' . $filename;
            PrivateFileTree::assertNoSymlinks($partial);
            PrivateFileTree::assertNoSymlinks($destination);
            clearstatcache();
            $existing = is_file($partial) ? (int)filesize($partial) : 0;
            if ($existing !== $offset) throw new RuntimeException('Upload offset mismatch; please resume the transfer.');
            $this->reserveReplacement($owner, $id, $directory, $partial, $size);
            $input = $output = null;
            try {
                $input = fopen($source, 'rb');
                $output = fopen($partial, 'c+b');
                if ($input === false || $output === false || fseek($output, $offset) !== 0) throw new RuntimeException('Unable to open transfer chunk streams.', 500);
                $copied = stream_copy_to_stream($input, $output);
                if ($copied !== $chunkBytes || !fflush($output)) {
                    ftruncate($output, $existing);
                    throw new RuntimeException('Unable to write complete transfer chunk.', 500);
                }
                fclose($output);
                $output = null;
                $uploaded = $existing + $copied;
                if ($index === $chunks - 1) {
                    if ($uploaded !== $size) throw new RuntimeException('Upload incomplete; please resume the transfer.');
                    if (!rename($partial, $destination)) throw new RuntimeException('Unable to finalize transfer upload.', 500);
                }
                return ['status' => $index === $chunks - 1 ? 'complete' : 'partial', 'uploaded' => $uploaded];
            } finally {
                if (is_resource($input)) fclose($input);
                if (is_resource($output)) fclose($output);
                $this->sync($owner, $id, $directory);
            }
        });
    }

    /** @return array{bytes: int, files: array<string, int>} */
    public function completedFiles(string $directory): array
    {
        $stats = $this->stats($directory);
        $files = array_filter($stats['files'], static fn($name) => !str_ends_with($name, '.part') && !str_ends_with($name, '.lock') && !str_starts_with($name, '.extplorer-stage-'), ARRAY_FILTER_USE_KEY);
        return ['bytes' => array_sum($files), 'files' => $files];
    }

    public function finish(string $owner, string $id): void
    {
        PrivateFileTree::remove($this->directory($owner, $id));
        $this->resources->release($this->reservationId($owner, $id), $owner);
    }

    public function cleanupExpired(): int
    {
        PrivateFileTree::assertNoSymlinks($this->root);
        if (!is_dir($this->root)) return 0;
        $removed = 0;
        $cutoff = time() - $this->policy->uploadStagingTtlSeconds();
        foreach (scandir($this->root) ?: [] as $user) {
            if (preg_match('/\A[a-f0-9]{64}\z/', $user) !== 1) continue;
            $parent = $this->root . '/' . $user;
            if (!is_dir($parent) || is_link($parent)) continue;
            foreach (scandir($parent) ?: [] as $id) {
                if (preg_match('/\A[A-Za-z0-9]{16,64}\z/', $id) !== 1) continue;
                $directory = $parent . '/' . $id;
                $removed += OperationLock::run($parent . '/.session-' . $id . '.lock', function () use ($directory, $cutoff): int {
                    if (is_link($directory) || !is_dir($directory)) return 0;
                    clearstatcache(true, $directory);
                    if ((int)filemtime($directory) > $cutoff) return 0;
                    PrivateFileTree::remove($directory);
                    return 1;
                });
            }
        }
        $this->resources->cleanupExpired();
        return $removed;
    }

    private function stats(string $directory, ?string $exclude = null): array
    {
        PrivateFileTree::assertNoSymlinks($directory);
        if (!is_dir($directory)) return ['bytes' => 0, 'files' => []];
        $files = [];
        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..') continue;
            $path = $directory . '/' . $name;
            if ($path === $exclude) continue;
            if (is_link($path) || !is_file($path)) throw new RuntimeException('Invalid transfer storage.', 500);
            $size = filesize($path);
            if ($size === false) throw new RuntimeException('Unable to read transfer staging size.', 500);
            $files[$name] = (int)$size;
        }
        return ['bytes' => array_sum($files), 'files' => $files];
    }

    private function reserveReplacement(string $owner, string $id, string $directory, string $path, int $size, ?string $exclude = null): void
    {
        $stats = $this->stats($directory, $exclude);
        $existing = $stats['files'][basename($path)] ?? 0;
        $this->reserve($owner, $id, $directory, $stats['bytes'] - $existing + $size,
            count($stats['files']) + (array_key_exists(basename($path), $stats['files']) ? 0 : 1));
    }

    private function reserve(string $owner, string $id, string $directory, int $bytes, int $files): void
    {
        try {
            $this->resources->reserve($owner, $this->reservationId($owner, $id), $bytes, $files,
                ['kind' => 'transfer', 'session_id' => $id, 'directory' => $directory]);
        } catch (RuntimeException $exception) {
            throw new RuntimeException('Transfer staging resource limit exceeded.', 413, $exception);
        }
        touch($directory);
    }

    private function sync(string $owner, string $id, string $directory): void
    {
        clearstatcache();
        $stats = $this->stats($directory);
        $this->reserve($owner, $id, $directory, $stats['bytes'], count($stats['files']));
    }

    private function reservationId(string $owner, string $id): string
    {
        return 'transfer:' . hash('sha256', $owner . '|' . $id);
    }

    private function ensureDirectory(string $directory): void
    {
        PrivateFileTree::assertNoSymlinks($directory);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Unable to create transfer staging storage.', 500);
        PrivateFileTree::assertNoSymlinks($directory);
    }
}
