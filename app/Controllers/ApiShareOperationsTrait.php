<?php

namespace App\Controllers;

use App\Services\LogService;
use Exception;

trait ApiShareOperationsTrait
{
    public function sharePolicy()
    {
        if (!can('read')) return $this->failForbidden();

        try {
            $settingsService = new \App\Services\SettingsService();
            $settings = $settingsService->getSettings();
            return $this->respond($this->buildSharePolicy($settings));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function shareCreate()
    {
        if (!can('read')) return $this->failForbidden();

        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $password = $json->password ?? null;
        $expires = $json->expires ?? null; // Timestamp or ISO string? Let's assume timestamp from frontend
        $mode = strtolower(trim((string)($json->mode ?? 'read')));

        if (!$path) return $this->fail('Path required');

        // Verify existence within user jail
        try {
            $absolutePath = $this->fs->resolvePath($path); // Throws if invalid/traversal
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        try {
            $settingsService = new \App\Services\SettingsService();
            $settings = $settingsService->getSettings();
            $policy = $this->buildSharePolicy($settings);

            $allowedModes = $policy['available_modes'] ?? ['read'];
            if (!in_array($mode, $allowedModes, true)) {
                return $this->fail('Share mode is not allowed by policy.');
            }

            if ($mode === 'upload' && !empty($absolutePath) && !is_dir($absolutePath)) {
                return $this->fail('Upload-mode shares must target a folder.');
            }

            if (!empty($settings['share_require_password']) && empty($password)) {
                return $this->fail('A password is required by policy for shared links.');
            }

            $expiresAt = $this->normalizeShareExpiry($expires, $settings);
            $service = new \App\Services\ShareService();
            $share = $service->createShare($path, session('username'), $password, $expiresAt, $mode);
            LogService::log('Create Share', $path);
            return $this->respond(['status' => 'success', 'share' => $share]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function shareDelete()
    {
        if (!can('read')) return $this->failForbidden();

        $json = $this->request->getJSON();
        $hash = $json->hash ?? null;
        if (!$hash) return $this->fail('Hash required');

        try {
            $service = new \App\Services\ShareService();
            $share = $service->getShareRaw($hash);

            // Allow admin to delete any share, user only their own
            if ($share && ($share['created_by'] === session('username') || can('admin_users'))) {
                // If it is a transfer, delete the physical directory as well.
                if (isset($share['source']) && $share['source'] === 'transfer') {
                    $dir = config('Storage')->uploads . '/shares/' . $share['path'];
                    $this->rrmdir($dir);
                }
                $service->deleteShare($hash);
                LogService::log('Delete Share', $hash);
                return $this->respond(['status' => 'success']);
            }
            return $this->failForbidden();
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function shareList()
    {
        if (!can('read')) return $this->failForbidden();

        try {
            $service = new \App\Services\ShareService();
            $shares = $service->listUserShares(session('username'));
            return $this->respond(['items' => $shares]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Normalizes and enforces share expiry policies.
     *
     * @param mixed $expires A timestamp or strtotime-compatible string.
     * @param array $settings Settings array from SettingsService.
     */
    private function normalizeShareExpiry($expires, array $settings): ?int
    {
        $now = time();
        $maxDays = (int)($settings['share_max_expiry_days'] ?? 30);
        if ($maxDays < 1) {
            $maxDays = 1;
        }
        if ($maxDays > 365) {
            $maxDays = 365;
        }

        $defaultDays = (int)($settings['share_default_expiry_days'] ?? 7);
        if ($defaultDays < 1) {
            $defaultDays = 1;
        }
        if ($defaultDays > $maxDays) {
            $defaultDays = $maxDays;
        }

        $requireExpiry = !empty($settings['share_require_expiry']);

        $expiresAt = null;
        if ($expires !== null && $expires !== '') {
            if (is_numeric($expires)) {
                $expiresAt = (int)$expires;
            } else {
                $ts = strtotime((string)$expires);
                if ($ts === false) {
                    throw new Exception('Share expiry is not a valid date.');
                }
                $expiresAt = $ts;
            }
        }

        if ($expiresAt === null && $requireExpiry) {
            $expiresAt = $now + ($defaultDays * 86400);
        }

        if ($expiresAt !== null && $expiresAt <= $now) {
            throw new Exception('Share expiry must be in the future.');
        }

        if ($expiresAt !== null) {
            $maxTimestamp = $now + ($maxDays * 86400);
            if ($expiresAt > $maxTimestamp) {
                throw new Exception("Share expiry exceeds the maximum allowed of {$maxDays} days.");
            }
        }

        return $expiresAt;
    }

    private function buildSharePolicy(array $settings): array
    {
        $maxDays = (int)($settings['share_max_expiry_days'] ?? 30);
        if ($maxDays < 1) {
            $maxDays = 1;
        }
        if ($maxDays > 365) {
            $maxDays = 365;
        }

        $defaultDays = (int)($settings['share_default_expiry_days'] ?? 7);
        if ($defaultDays < 1) {
            $defaultDays = 1;
        }
        if ($defaultDays > $maxDays) {
            $defaultDays = $maxDays;
        }

        $allowUploadMode = !empty($settings['allow_public_uploads']);
        $availableModes = ['read'];
        if ($allowUploadMode) {
            $availableModes[] = 'upload';
        }

        $allowedExtensions = $this->normalizeAllowedExtensions($settings['share_upload_allowed_extensions'] ?? []);
        $allowedExtensionsLabel = '';
        if (!empty($allowedExtensions)) {
            $allowedExtensionsLabel = implode(', ', array_map(static fn (string $ext): string => '.' . $ext, $allowedExtensions));
        }

        return [
            'require_password' => !empty($settings['share_require_password']),
            'require_expiry' => !empty($settings['share_require_expiry']),
            'default_expiry_days' => $defaultDays,
            'max_expiry_days' => $maxDays,
            'allow_upload_mode' => $allowUploadMode,
            'available_modes' => $availableModes,
            'upload_policy' => [
                'max_file_mb' => (int)($settings['upload_max_file_mb'] ?? 0),
                'allowed_extensions' => $allowedExtensions,
                'allowed_extensions_label' => $allowedExtensionsLabel,
                'quota_mb' => (int)($settings['share_upload_quota_mb'] ?? 0),
                'max_files' => (int)($settings['share_upload_max_files'] ?? 0),
            ],
        ];
    }

    /**
     * Normalize allowed extensions from settings input.
     *
     * @param mixed $raw
     * @return array<int, string>
     */
    private function normalizeAllowedExtensions($raw): array
    {
        $extensions = [];

        if (is_string($raw)) {
            $extensions = preg_split('/[\s,;]+/', $raw) ?: [];
        } elseif (is_array($raw)) {
            $extensions = $raw;
        }

        $normalized = [];
        foreach ($extensions as $ext) {
            $ext = strtolower(trim((string)$ext));
            $ext = ltrim($ext, '.');
            if ($ext === '') {
                continue;
            }
            if (!preg_match('/^[a-z0-9]+$/', $ext)) {
                continue;
            }
            $normalized[] = $ext;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($path) && !is_link($path)) {
                $this->rrmdir($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($dir);
    }


}
