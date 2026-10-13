<?php

namespace App\Services;

use App\Models\UserModel;
use App\Services\VFS\FileCapabilities;
use App\Services\VFS\IFileSystem;
use App\Services\VFS\LocalAdapter;
use App\Services\VFS\PathPolicy;
use RuntimeException;

/** Shared filename, folder, conflict, size and local account-quota rules. */
final class FileUploadPolicy
{
    public function filename(string $name): string
    {
        return (new FileNamePolicy())->assertSafe(trim($name));
    }

    public function relativeSegments(string $path): array
    {
        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, '/')) throw new RuntimeException('Upload path must be relative.');
        $relative = PathPolicy::normalizeRelative($path);
        if ($relative === '') return [];
        $segments = explode('/', $relative);
        foreach ($segments as $segment) $this->filename($segment);
        return $segments;
    }

    public function conflict(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['replace', 'skip', 'keep_both'], true) ? $value : 'replace';
    }

    /** @return array{parent: string, filename: string, relativePath: string, path: string} */
    public function target(string $base, string $filename, string $relative): array
    {
        $segments = $this->relativeSegments($relative);
        $filename = $this->filename($segments === [] ? $filename : array_pop($segments));
        $base = PathPolicy::normalizeRelative($base);
        $parent = implode('/', array_filter(array_merge([$base], $segments), static fn($part) => $part !== ''));
        return ['parent' => $parent, 'filename' => $filename, 'relativePath' => implode('/', [...$segments, $filename]), 'path' => ($parent === '' ? '' : $parent . '/') . $filename];
    }

    public function exceedsSize(int $bytes, array $settings): bool
    {
        $limit = max(0, (int)($settings['upload_max_file_mb'] ?? 0)) * 1024 * 1024;
        return $limit > 0 && $bytes > $limit;
    }

    public function assertSize(int $bytes): void
    {
        if ($bytes < 0 || $this->exceedsSize($bytes, (new SettingsService())->getSettings())) throw new RuntimeException('Upload exceeds the configured size limit.', 413);
    }

    public function withAccountQuota(string $owner, IFileSystem $fs, string $path, int $bytes, callable $callback): mixed
    {
        $physical = FileCapabilities::localPath($fs, $path);
        if ($physical === null) return $callback();
        $user = (new UserModel())->getUser($owner);
        if ($user === null) throw new RuntimeException('Upload account is unavailable.');
        $root = (new LocalAdapter(config('Storage')->fileManagerRoot))->resolvePath((string)($user['home_dir'] ?? '/'));
        // Shared and external storage have their own capacity; an account's
        // home quota must not classify unrelated remote or shared trees.
        if (!PathPolicy::isWithinRoot($root, $physical)) return $callback();
        return OperationLock::run(config('Storage')->cache . '/dav/quota-' . hash('sha256', $owner) . '.lock', function () use ($root, $physical, $bytes, $callback): mixed {
            $quota = max(0, (int)(new SettingsService())->get('quota_per_user_mb', 0)) * 1024 * 1024;
            if ($quota > 0) {
                clearstatcache();
                $existing = is_file($physical) ? (int)filesize($physical) : 0;
                $usage = (new LocalAdapter($root))->getDirectorySize('');
                if ($usage - $existing + $bytes > $quota) throw new RuntimeException('Upload would exceed the configured per-user storage quota.');
            }
            return $callback();
        });
    }
}
