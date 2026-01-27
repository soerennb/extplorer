<?php

namespace App\Controllers;

use App\Services\ShareService;
use App\Services\VFS\LocalAdapter;
use CodeIgniter\API\ResponseTrait;

class ShareController extends BaseController
{
    use ResponseTrait;

    public function index(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);
        $supportedLocales = ['en', 'de', 'fr'];
        $locale = $this->detectLocale($supportedLocales);
        $translations = $this->loadTranslations($locale);

        if (!$share) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Link expired or invalid.");
        }

        // Password Protection Check
        if ($share['password_hash']) {
            // Check if verified in session
            $sessionKey = 'share_verified_' . $hash;
            if (!session($sessionKey)) {
                return view('shared_password', [
                    'hash' => $hash,
                    'locale' => $locale,
                    'translations' => $translations,
                ]);
            }
        }

        // Serve Content
        $root = WRITEPATH . 'file_manager_root/' . $share['path'];
        
        // Handle Transfer Source
        if (isset($share['source']) && $share['source'] === 'transfer') {
            $root = WRITEPATH . 'uploads/shares/' . $share['path'];
        }

        if (!file_exists($root)) {
             throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Shared content missing.");
        }

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
        $localesToTry = array_values(array_unique(['en', $locale]));
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
        $service = new ShareService();
        $password = $this->request->getPost('password');
        $supportedLocales = ['en', 'de', 'fr'];
        $locale = $this->detectLocale($supportedLocales);
        $translations = $this->loadTranslations($locale);
        $invalidPasswordMessage = $translations['shared_invalid_password'] ?? 'Invalid Password';

        if ($service->verifyPassword($hash, $password)) {
            session()->set('share_verified_' . $hash, true);
            return redirect()->to('/s/' . $hash);
        }

        return redirect()->back()->with('error', $invalidPasswordMessage);
    }

    public function download(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);
        if (!$share) return $this->failNotFound();

        // Password check
        if ($share['password_hash'] && !session('share_verified_' . $hash)) {
            return $this->failForbidden();
        }

        $rootBase = WRITEPATH . 'file_manager_root/';
        if (isset($share['source']) && $share['source'] === 'transfer') {
            $rootBase = WRITEPATH . 'uploads/shares/';
        }

        $basePath = $rootBase . $share['path'];
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
            $tempZip = WRITEPATH . 'cache/' . uniqid('share_', true) . '.zip';
            $this->zipDirectory($fullPath, $tempZip);

            // Clean up after the response is sent.
            register_shutdown_function(static function () use ($tempZip): void {
                @unlink($tempZip);
            });

            return $this->response->download($tempZip, null)->setFileName($zipName);
        }

        if ($inline) {
            $mime = mime_content_type($fullPath);

            return $this->response
                ->setHeader('Content-Type', $mime)
                ->setHeader('Content-Disposition', 'inline; filename="' . basename($fullPath) . '"')
                ->setBody(file_get_contents($fullPath));
        }

        return $this->response->download($fullPath, null);
    }

    // JSON API for the shared view (listing subfolders)
    public function ls(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);
        if (!$share) return $this->failNotFound();

        // Password check
        if ($share['password_hash'] && !session('share_verified_' . $hash)) {
            return $this->failForbidden();
        }

        $rootBase = WRITEPATH . 'file_manager_root/';
        if (isset($share['source']) && $share['source'] === 'transfer') {
            $rootBase = WRITEPATH . 'uploads/shares/';
        }

        $basePath = $rootBase . $share['path'];
        if (is_file($basePath)) {
            return $this->failForbidden();
        }

        $subPath = $this->request->getGet('path') ?? '';
        $subPath = ltrim($subPath, '/');

        try {
            $fs = new LocalAdapter($basePath);
            $items = $fs->listDirectory($subPath, false);
            return $this->respond(['items' => $items]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Create a zip archive for a directory using paths relative to the directory root.
     */
    private function zipDirectory(string $sourceDir, string $destinationZip): void
    {
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
            $full = $item->getPathname();
            $relative = substr($full, $baseLen);

            if ($item->isDir()) {
                $zip->addEmptyDir(str_replace(DIRECTORY_SEPARATOR, '/', $relative));
                continue;
            }

            $zip->addFile($full, str_replace(DIRECTORY_SEPARATOR, '/', $relative));
        }

        $zip->close();
    }
}
