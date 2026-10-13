<?php

namespace App\Services;

use App\Services\VFS\FileCapabilities;
use App\Services\VFS\IFileSystem;
use App\Services\VFS\StreamWritableFileSystem;
use App\Services\VFS\UploadTarget;
use App\Services\VFS\VirtualAdapter;
use RuntimeException;

/** One preparation and activation flow for every authenticated upload transport. */
final class FileUploadService
{
    private FileUploadPolicy $policy;
    private UploadTargetResolver $resolver;

    public function __construct(?FileUploadPolicy $policy = null, ?UploadTargetResolver $resolver = null)
    {
        $this->policy = $policy ?? new FileUploadPolicy();
        $this->resolver = $resolver ?? new UploadTargetResolver();
    }

    public function prepare(IFileSystem $fs, string $base, string $filename, string $relative): array
    {
        $target = $this->policy->target($base, $filename, $relative);
        if ($fs instanceof VirtualAdapter && $target['parent'] === '') throw new RuntimeException('Open a writable folder before uploading files.');
        $base = \App\Services\VFS\PathPolicy::normalizeRelative($base);
        $baseMeta = $fs->getMetadata($base);
        if ($baseMeta === null) $fs->listDirectory($base);
        elseif (($baseMeta['type'] ?? '') !== 'dir') throw new RuntimeException('Target is not a directory.');
        $segments = $this->policy->relativeSegments($relative);
        if ($segments !== []) array_pop($segments);
        $parent = $base;
        foreach ($segments as $segment) {
            $parent = ($parent === '' ? '' : $parent . '/') . $segment;
            $meta = $fs->getMetadata($parent);
            if ($meta === null && !$fs->createDirectory($parent)) throw new RuntimeException('Unable to create upload folder.');
            if ($meta !== null && ($meta['type'] ?? '') !== 'dir') throw new RuntimeException('Upload folder is not a directory.');
        }
        return $target;
    }

    public function upload(string $source, IFileSystem $fs, string $owner, array $connection, string $base, string $filename, string $relative, string $conflict): array
    {
        $prepared = $this->prepare($fs, $base, $filename, $relative);
        if (!(new FileNamePolicy())->isAllowed($prepared['filename'], session('allowed_extensions'), session('blocked_extensions'))) throw new RuntimeException('Uploading files with this extension is not allowed.');
        $target = $this->resolver->capture($owner, $connection, $prepared['path']);
        return $this->accept($source, $target, $conflict, $prepared['relativePath']);
    }

    public function accept(string $source, UploadTarget $target, string $conflict, string $relativePath): array
    {
        PrivateFileTree::assertNoSymlinks($source);
        if (!is_file($source)) throw new RuntimeException('Upload payload is missing.');
        $size = (int)filesize($source);
        $this->policy->assertSize($size);
        $quarantine = new UploadQuarantineService(null, null, $this->resolver);
        if ($quarantine->enabled()) {
            // Resolve once before accepting the payload so stale grants cannot
            // enqueue a target that is already unauthorized.
            $pending = $quarantine->stage($source, $target, ['owner' => $target->owner, 'filename' => basename($target->path),
                'relative_path' => $relativePath, 'conflict' => $conflict, 'source' => 'authenticated-upload']);
            return ['status' => 'pending_scan', 'quarantine_id' => $pending['id'], 'filename' => basename($target->path), 'path' => $relativePath];
        }
        $result = $this->activate($source, $target, $conflict);
        $this->resolver->release($target);
        return ['status' => $result['status'], 'filename' => basename($result['path']),
            'path' => ($directory = dirname($relativePath)) === '.' ? basename($result['path']) : $directory . '/' . basename($result['path'])];
    }

    public function activate(string $payload, UploadTarget $target, string $conflict): array
    {
        [$fs, $path] = $this->resolver->resolve($target);
        if (!$fs instanceof StreamWritableFileSystem) throw new RuntimeException('Stream writing is unavailable for this storage.', 422);
        $size = (int)filesize($payload);
        $this->policy->assertSize($size);
        $conflict = $this->policy->conflict($conflict);
        $key = hash('sha256', $target->kind . '|' . $target->fingerprint . '|' . dirname($path));
        return OperationLock::run(config('Storage')->runtime . '/.upload-target-' . $key . '.lock', function () use ($fs, $path, $payload, $size, $conflict, $target): array {
            $existing = $fs->getMetadata($path);
            if ($existing !== null) {
                if ($conflict === 'skip') return ['status' => 'skipped', 'path' => $path];
                if (($existing['type'] ?? '') !== 'file') throw new RuntimeException('An upload directory already uses this name.');
                if ($conflict === 'keep_both') $path = $this->unusedName($fs, $path);
            }
            return $this->policy->withAccountQuota($target->owner, $fs, $path, $size, function () use ($fs, $path, $payload): array {
                $input = fopen($payload, 'rb');
                if ($input === false) throw new RuntimeException('Unable to open upload payload.');
                try { if (!$fs->writeStream($path, $input)) throw new RuntimeException('Unable to activate uploaded file.'); }
                finally { fclose($input); }
                return ['status' => 'success', 'path' => $path];
            });
        });
    }

    private function unusedName(IFileSystem $fs, string $path): string
    {
        $info = pathinfo($path);
        $prefix = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'] . '/';
        $extension = isset($info['extension']) && $info['extension'] !== '' ? '.' . $info['extension'] : '';
        for ($number = 1; $number < 1000; $number++) {
            $candidate = $prefix . $info['filename'] . ' (' . $number . ')' . $extension;
            if ($fs->getMetadata($candidate) === null) return $candidate;
        }
        throw new RuntimeException('Unable to create a non-conflicting filename.');
    }
}
