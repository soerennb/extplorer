<?php

namespace App\Controllers;

use App\Services\ShareService;
use App\Services\LogService;
use App\Services\VFS\LocalAdapter;
use App\Services\DownloadHeaders;
use App\Services\ResourcePolicy;

class ShareController extends BaseController
{
    use ApiResponseTrait;

    private const VERIFIED_SESSION_TTL = 1800;

    public function index(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);
        $supportedLocales = config('I18n')->supportedLocales();
        $locale = $this->detectLocale($supportedLocales);
        $translations = $this->loadTranslations($locale);
        $settingsService = new \App\Services\SettingsService();
        $settings = $settingsService->getSettings();
        $uploadMaxFileMb = (int)($settings['upload_max_file_mb'] ?? 0);

        if (!$share) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Link expired or invalid.");
        }

        // Password Protection Check
        if (!$this->isShareVerified($hash, $share)) {
            return view('shared_password', [
                'hash' => $hash,
                'locale' => $locale,
                'translations' => $translations,
            ]);
        }

        [, $basePath] = $this->resolveSharePaths($share);

        // Serve Content
        $root = $basePath;

        if (!file_exists($root)) {
             throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Shared content missing.");
        }

        $isUploadMode = ($share['mode'] ?? 'read') === 'upload';
        $uploadPolicy = $isUploadMode ? $this->buildUploadPolicy($settings, $basePath, $share) : [];

        if (is_file($root)) {
            // Direct download/preview for single file share?
            // Usually showing a preview page is better UX
            return view('shared', [
                'share' => $share, 
                'is_file' => true,
                'filename' => basename($share['path']),
                'size' => filesize($root),
                'hash' => $hash,
                'locale' => $locale,
                'translations' => $translations,
                'uploadMaxFileMb' => $uploadMaxFileMb,
                'uploadPolicy' => $uploadPolicy,
            ]);
        }

        // It's a directory
        // We need a file list. We can reuse the Vue app but strictly configured?
        // Or a simpler server-side rendered list for MVP? 
        // Let's go with a simpler Vue instance using the 'shared' layout.
        
        return view('shared', [
            'share' => $share, 
            'is_file' => false,
            'hash' => $hash,
            'locale' => $locale,
            'translations' => $translations,
            'uploadMaxFileMb' => $uploadMaxFileMb,
            'uploadPolicy' => $uploadPolicy,
        ]);
    }

    /**
     * Detects the best-fit locale from the Accept-Language header.
     *
     * Falls back to English when negotiation fails.
     *
     * @param array<int, string> $supportedLocales
     */
    private function detectLocale(array $supportedLocales): string
    {
        try {
            $negotiated = $this->request->negotiateLanguage($supportedLocales);
            if (is_string($negotiated) && in_array($negotiated, $supportedLocales, true)) {
                return $negotiated;
            }
        } catch (\Throwable $e) {
            // Ignore negotiation errors and fall back to English.
        }

        return 'en';
    }

    /**
     * Loads translations from the public i18n JSON files.
     *
     * @return array<string, string>
     */
    private function loadTranslations(string $locale): array
    {
        $fallbackLocale = config('I18n')->fallbackLocale;
        $localesToTry = array_values(array_unique([$fallbackLocale, $locale]));
        $translations = [];

        foreach ($localesToTry as $loc) {
            $path = FCPATH . 'assets/i18n/' . $loc . '.json';
            if (!is_file($path)) {
                continue;
            }

            $raw = file_get_contents($path);
            if ($raw === false) {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            // Later locales win, detected locale overrides English.
            $translations = array_merge($translations, $decoded);
        }

        return $translations;
    }

    public function auth(string $hash)
    {
        $throttler = \Config\Services::throttler();
        $authThrottleKey = 'share-auth-' . $hash . '-' . hash('sha256', $this->request->getIPAddress());
        if ($throttler->check($authThrottleKey, 10, MINUTE) === false) {
            LogService::log('Share Auth Throttled', $hash, 'Public share auth rate limit exceeded', 'Public');
            return redirect()->back()->with('error', 'Too many requests. Please slow down.');
        }

        $service = new ShareService();
        $password = $this->request->getPost('password');
        $supportedLocales = config('I18n')->supportedLocales();
        $locale = $this->detectLocale($supportedLocales);
        $translations = $this->loadTranslations($locale);
        $invalidPasswordMessage = $translations['shared_invalid_password'] ?? 'Invalid Password';

        $share = $service->getShare($hash);
        if ($share && $service->verifyPassword($hash, $password)) {
            $expiresAt = (int)($share['expires_at'] ?? 0);
            $verifiedUntil = time() + self::VERIFIED_SESSION_TTL;
            if ($expiresAt > 0) {
                $verifiedUntil = min($verifiedUntil, $expiresAt);
            }

            session()->set('share_verified_' . $hash, [
                'verified_at' => time(),
                'expires_at' => $verifiedUntil,
            ]);
            return redirect()->to('/s/' . $hash);
        }

        LogService::log('Share Auth Failed', $hash, 'Invalid password attempt', 'Public');
        return redirect()->back()->with('error', $invalidPasswordMessage);
    }

    public function download(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);
        if (!$share) return $this->failNotFound();

        // Password check
        if (!$this->isShareVerified($hash, $share)) {
            return $this->failForbidden();
        }

        if (($share['mode'] ?? 'read') === 'upload') {
            return $this->failForbidden('Downloads are not allowed for this share.');
        }

        [, $basePath] = $this->resolveSharePaths($share);
        $inline = $this->request->getGet('inline');

        $subPath = $this->request->getGet('path');
        if (is_file($basePath)) {
            if ($subPath) {
                return $this->failForbidden();
            }
            $fs = new LocalAdapter(dirname($basePath));
            $relPath = basename($basePath);
        } else {
            $fs = new LocalAdapter($basePath);
            $subPath = $subPath ?? '';
            $subPath = ltrim($subPath, '/');
            $relPath = $subPath === '' ? '.' : $subPath;
        }

        $fullPath = $fs->resolvePath($relPath);

        if (!file_exists($fullPath)) return $this->failNotFound();
        if (is_file($fullPath) && (int)(filesize($fullPath) ?: 0) > (new ResourcePolicy())->maxDownloadBytes()) {
            return $this->fail('File exceeds the configured download size limit.', 413);
        }

        // Track Download (only for main download, not inline previews if possible, or count all?)
        // Usually we count only "File Downloads" or "Zip Downloads". 
        // If it is inline (preview), maybe we skip counting? 
        // WeTransfer counts downloads. Let's count unless inline image/video.
        if (!$inline) {
             $service->incrementDownloads($hash);
             
             // Send notification if requested
             if (isset($share['notify_download']) && $share['notify_download']) {
                 // Check if already notified for this session to avoid spam? 
                 // For now, simple implementation:
                 $emailService = new \App\Services\EmailService();
                 $emailService->sendDownloadNotification($share);
             }
        }

        if (is_dir($fullPath)) {
            // Zip directory outside the share root to avoid path resolution issues.
            $zipName = basename($fullPath) . '.zip';
            $tempZip = config('Storage')->cache . '/' . uniqid('share_', true) . '.zip';
            $this->zipDirectory($fullPath, $tempZip);

            // Clean up after the response is sent.
            register_shutdown_function(static function () use ($tempZip): void {
                @unlink($tempZip);
            });

            return $this->response->download($tempZip, null)
                ->setFileName(DownloadHeaders::filename($zipName, 'shared.zip'))
                ->setHeader('X-Content-Type-Options', 'nosniff');
        }

        $filename = basename($fullPath);
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
        if ($inline && !DownloadHeaders::isSafeInline($filename, $mime)) {
            $inline = false;
        }

        if ($inline) {
            return $this->response
                ->download($fullPath, null)
                ->setFileName(DownloadHeaders::filename($filename))
                ->setHeader('Content-Type', $mime)
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Content-Disposition', DownloadHeaders::contentDisposition('inline', $filename));
        }

        return $this->response
            ->download($fullPath, null)
            ->setFileName(DownloadHeaders::filename($filename))
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', DownloadHeaders::contentDisposition('attachment', $filename));
    }

    // JSON API for the shared view (listing subfolders)
    public function ls(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);
        if (!$share) return $this->failNotFound();

        // Password check
        if (!$this->isShareVerified($hash, $share)) {
            return $this->failForbidden();
        }

        [, $basePath] = $this->resolveSharePaths($share);
        if (is_file($basePath)) {
            return $this->failForbidden();
        }

        $subPath = $this->request->getGet('path') ?? '';
        $subPath = ltrim($subPath, '/');

        try {
            $fs = new LocalAdapter($basePath);
            $items = $fs->listDirectory($subPath, false);
            $policy = null;
            if (($share['mode'] ?? 'read') === 'upload') {
                $settingsService = new \App\Services\SettingsService();
                $settings = $settingsService->getSettings();
                $policy = $this->buildUploadPolicy($settings, $basePath, $share);
            }
            return $this->respond([
                'items' => $items,
                'upload_policy' => $policy,
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Public upload endpoint for upload-mode shares.
     */
    public function upload(string $hash)
    {
        $throttler = \Config\Services::throttler();
        $uploadThrottleKey = 'share-upload-' . $hash . '-' . hash('sha256', $this->request->getIPAddress());
        if ($throttler->check($uploadThrottleKey, 30, MINUTE) === false) {
            LogService::log('Share Upload Throttled', $hash, 'Public share upload rate limit exceeded', 'Public');
            return $this->fail('Too many requests. Please slow down.', 429);
        }

        $service = new ShareService();
        $share = $service->getShare($hash);
        if (!$share) {
            return $this->failNotFound();
        }

        // Password check
        if (!$this->isShareVerified($hash, $share)) {
            LogService::log('Share Upload Forbidden', $hash, 'Upload blocked: password not verified', 'Public');
            return $this->failForbidden();
        }

        if (($share['mode'] ?? 'read') !== 'upload') {
            LogService::log('Share Upload Forbidden', $hash, 'Upload blocked: share is not upload mode', 'Public');
            return $this->failForbidden('Uploads are not allowed for this share.');
        }

        [, $basePath] = $this->resolveSharePaths($share);
        if (!is_dir($basePath)) {
            return $this->failForbidden();
        }

        $subPath = (string)($this->request->getPost('path') ?? '');
        $subPath = ltrim($subPath, '/');

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->fail($file ? $file->getErrorString() : 'No file uploaded');
        }

        try {
            $settingsService = new \App\Services\SettingsService();
            $settings = $settingsService->getSettings();
            $policy = $this->buildUploadPolicy($settings, $basePath, $share);

            $fileSize = (int)$file->getSize();
            $maxMb = (int)($settings['upload_max_file_mb'] ?? 0);
            if ($maxMb > 0) {
                $maxBytes = $maxMb * 1024 * 1024;
                if ($fileSize > $maxBytes) {
                    return $this->fail("File exceeds the maximum allowed upload size of {$maxMb} MB.");
                }
            }

            $allowedExts = $policy['allowed_extensions'];
            if (!empty($allowedExts)) {
                $name = (string)$file->getClientName();
                $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
                if ($ext === '' || !in_array($ext, $allowedExts, true)) {
                    $allowedLabel = $policy['allowed_extensions_label'];
                    return $this->fail(
                        $allowedLabel !== ''
                            ? "File type not allowed. Allowed types: {$allowedLabel}."
                            : 'File type not allowed.',
                        400
                    );
                }
            }

            $name = $this->sanitizePublicUploadFilename((string)$file->getClientName());
            $lock = $this->openShareUploadLock($hash);
            try {
                $policy = $this->buildUploadPolicy($settings, $basePath, $share);
                $quotaBytes = (int)$policy['quota_bytes'];
                $quotaUsed = (int)$policy['quota_used_bytes'];
                if ($quotaBytes > 0 && ($quotaUsed + $fileSize) > $quotaBytes) {
                    return $this->fail('Upload would exceed the share quota.', 400);
                }

                $maxFiles = (int)$policy['max_files'];
                $filesUsed = (int)$policy['files_used'];
                if ($maxFiles > 0 && ($filesUsed + 1) > $maxFiles) {
                    return $this->fail('Upload would exceed the maximum number of files for this share.', 400);
                }

                $fs = new LocalAdapter($basePath);
                $targetDir = $fs->resolvePath($subPath);
                if (!is_dir($targetDir)) {
                    return $this->fail('Target directory does not exist.');
                }

                if (!$file->move($targetDir, $name)) {
                    return $this->fail('Unable to store uploaded file.', 500);
                }
            } finally {
                $this->closeShareUploadLock($lock);
            }
            $postPolicy = $this->buildUploadPolicy($settings, $basePath, $share);

            return $this->respond([
                'status' => 'success',
                'name' => $name,
                'path' => $subPath,
                'upload_policy' => $postPolicy,
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Resolve root and absolute base path for a share.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveSharePaths(array $share): array
    {
        $rootBase = rtrim(config('Storage')->fileManagerRoot, '/\\');
        if (isset($share['source']) && $share['source'] === 'transfer') {
            $rootBase = rtrim(config('Storage')->uploads, '/\\') . DIRECTORY_SEPARATOR . 'shares';
        }

        $resolver = new LocalAdapter($rootBase);
        return [$rootBase, $resolver->resolvePath((string)($share['path'] ?? ''))];
    }

    /**
     * Build the upload policy/limits payload for the shared page.
     *
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $share
     * @return array<string, mixed>
     */
    private function buildUploadPolicy(array $settings, string $basePath, array $share): array
    {
        $maxFileMb = (int)($settings['upload_max_file_mb'] ?? 0);
        $maxFileBytes = $maxFileMb > 0 ? $maxFileMb * 1024 * 1024 : 0;

        $allowedExtensions = $this->normalizeAllowedExtensions($settings['share_upload_allowed_extensions'] ?? []);
        $allowedLabel = '';
        if (!empty($allowedExtensions)) {
            $allowedLabel = implode(', ', array_map(static fn (string $ext): string => '.' . $ext, $allowedExtensions));
        }

        $quotaMb = (int)($settings['share_upload_quota_mb'] ?? 0);
        $quotaBytes = $quotaMb > 0 ? $quotaMb * 1024 * 1024 : 0;

        $maxFiles = (int)($settings['share_upload_max_files'] ?? 0);

        $usage = $this->collectUsageStats($basePath);
        $quotaRemaining = $quotaBytes > 0 ? max(0, $quotaBytes - $usage['bytes']) : null;
        $filesRemaining = $maxFiles > 0 ? max(0, $maxFiles - $usage['files']) : null;

        return [
            'mode' => $share['mode'] ?? 'read',
            'max_file_mb' => $maxFileMb,
            'max_file_bytes' => $maxFileBytes,
            'allowed_extensions' => $allowedExtensions,
            'allowed_extensions_label' => $allowedLabel,
            'quota_mb' => $quotaMb,
            'quota_bytes' => $quotaBytes,
            'quota_used_bytes' => $usage['bytes'],
            'quota_remaining_bytes' => $quotaRemaining,
            'max_files' => $maxFiles,
            'files_used' => $usage['files'],
            'files_remaining' => $filesRemaining,
        ];
    }

    /**
     * Normalize allowed extension configuration from settings.
     *
     * @param mixed $raw
     * @return array<int, string>
     */
    private function normalizeAllowedExtensions($raw): array
    {
        $extensions = [];

        if (is_string($raw)) {
            $parts = preg_split('/[\s,;]+/', $raw) ?: [];
            $extensions = $parts;
        } elseif (is_array($raw)) {
            $extensions = $raw;
        }

        $normalized = [];
        foreach ($extensions as $ext) {
            $ext = strtolower((string)$ext);
            $ext = ltrim($ext, '.');
            if ($ext === '') {
                continue;
            }
            $normalized[] = $ext;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    /**
     * Collect recursive usage stats for a share directory.
     *
     * @return array{bytes: int, files: int}
     */
    private function collectUsageStats(string $basePath): array
    {
        if (!file_exists($basePath)) {
            return ['bytes' => 0, 'files' => 0];
        }

        if (is_file($basePath)) {
            return [
                'bytes' => (int)@filesize($basePath),
                'files' => 1,
            ];
        }

        $bytes = 0;
        $files = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $item) {
                if ($item->isLink()) {
                    throw new \RuntimeException('Share usage cannot be calculated for symbolic links.');
                }
                if (!$item->isFile()) {
                    continue;
                }
                $files++;
                $bytes += (int)$item->getSize();
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to verify share upload quota.', 0, $e);
        }

        return ['bytes' => $bytes, 'files' => $files];
    }

    /**
     * Create a zip archive for a directory using paths relative to the directory root.
     */
    private function zipDirectory(string $sourceDir, string $destinationZip): void
    {
        $policy = new ResourcePolicy();
        $entries = 0;
        $bytes = 0;
        $zip = new \ZipArchive();
        if ($zip->open($destinationZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create zip archive.');
        }

        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $baseLen = strlen($sourceDir) + 1;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $entries++;
            $bytes += $item->isFile() ? max(0, (int)($item->getSize() ?: 0)) : 0;
            if ($entries > $policy->maxArchiveEntries() || $bytes > $policy->maxArchiveBytes()) {
                $zip->close();
                @unlink($destinationZip);
                throw new \RuntimeException('Public share archive exceeds the configured safety limits.');
            }
            $full = $item->getPathname();
            $relative = substr($full, $baseLen);

            if ($item->isLink()) {
                $zip->close();
                @unlink($destinationZip);
                throw new \RuntimeException('Refusing to expose symbolic links in a public share archive.');
            }

            if ($item->isDir()) {
                $zip->addEmptyDir(str_replace(DIRECTORY_SEPARATOR, '/', $relative));
                continue;
            }

            $zip->addFile($full, str_replace(DIRECTORY_SEPARATOR, '/', $relative));
        }

        $zip->close();
    }

    private function isShareVerified(string $hash, array $share): bool
    {
        if (empty($share['password_hash'])) {
            return true;
        }

        $value = session('share_verified_' . $hash);
        if (!is_array($value)) {
            return false;
        }

        $expiresAt = (int)($value['expires_at'] ?? 0);
        if ($expiresAt <= time()) {
            session()->remove('share_verified_' . $hash);
            return false;
        }

        $shareExpiresAt = (int)($share['expires_at'] ?? 0);
        if ($shareExpiresAt > 0 && $expiresAt > $shareExpiresAt) {
            session()->remove('share_verified_' . $hash);
            return false;
        }

        return true;
    }

    private function sanitizePublicUploadFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new \RuntimeException('Invalid filename.');
        }

        if (strlen($filename) > 255 || preg_match('/[\x00-\x1F\x7F]/', $filename)) {
            throw new \RuntimeException('Invalid filename.');
        }

        return $filename;
    }

    /** @return resource */
    private function openShareUploadLock(string $hash)
    {
        $lockPath = config('Storage')->runtime . DIRECTORY_SEPARATOR . 'share-upload-' . $hash . '.lock';
        $lock = @fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new \RuntimeException('Unable to reserve share upload quota.');
        }

        return $lock;
    }

    /** @param resource $lock */
    private function closeShareUploadLock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
