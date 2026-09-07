<?php

namespace App\Services;

use App\Services\VFS\LocalAdapter;
use App\Services\VFS\PathPolicy;
use Exception;

class ShareService
{
    private string $sharesFile;

    public function __construct(?string $sharesFile = null)
    {
        $this->sharesFile = $sharesFile ?? (config('Storage')->state . '/shares.php');

        if (!file_exists($this->sharesFile)) {
            $this->saveShares([]);
        }
    }

    private function getShares(): array
    {
        return AtomicFileStore::read($this->sharesFile);
    }

    private function saveShares(array $shares): void
    {
        AtomicFileStore::write($this->sharesFile, $shares);
    }

    /**
     * Creates a new share link.
     */
    public function createShare(string $path, string $user, ?string $password = null, ?int $expiresAt = null, string $mode = 'read', array $meta = []): array
    {
        $path = PathPolicy::normalizeRelative($path);

        $baseRoot = rtrim(config('Storage')->fileManagerRoot, '/\\') . '/';
        if (($meta['source'] ?? '') === 'transfer') {
            $baseRoot = rtrim(config('Storage')->uploads, '/\\') . '/shares/';
        }
        $targetPath = $baseRoot . $path;
        $type = is_dir($targetPath) ? 'dir' : (is_file($targetPath) ? 'file' : 'dir');

        return AtomicFileStore::transaction($this->sharesFile, function (array &$shares) use (
            $path,
            $type,
            $user,
            $password,
            $expiresAt,
            $mode,
            $meta
        ): array {
            // Generate a 128-bit opaque identifier while holding the state lock.
            $hash = bin2hex(random_bytes(16));
            while (isset($shares[$hash])) {
                $hash = bin2hex(random_bytes(16));
            }

            $share = array_merge([
                'hash' => $hash,
                'path' => $path,
                'type' => $type,
                'created_by' => $user,
                'created_at' => time(),
                'expires_at' => $expiresAt,
                'password_hash' => $password ? password_hash($password, PASSWORD_DEFAULT) : null,
                'mode' => $mode,
                'downloads' => 0,
            ], $meta);
            $shares[$hash] = $share;
            return $share;
        });
    }

    public function incrementDownloads(string $hash): void
    {
        AtomicFileStore::transaction($this->sharesFile, function (array &$shares) use ($hash): void {
            if (isset($shares[$hash])) {
                $shares[$hash]['downloads'] = ($shares[$hash]['downloads'] ?? 0) + 1;
            }
        });
    }

    /**
     * Retrieves a share by hash, validating expiration.
     */
    public function getShare(string $hash): ?array
    {
        $share = $this->getShareRaw($hash);
        if (!$share) {
            return null;
        }

        // Check Expiration
        if (!empty($share['expires_at']) && time() >= (int)$share['expires_at']) {
            return null; // Or return 'expired' status if logic demands
        }

        return $share;
    }

    /**
     * Retrieves a share by hash without expiration checks.
     */
    public function getShareRaw(string $hash): ?array
    {
        $shares = $this->getShares();
        return $shares[$hash] ?? null;
    }

    /**
     * Deletes a share.
     */
    public function deleteShare(string $hash): bool
    {
        return AtomicFileStore::transaction($this->sharesFile, function (array &$shares) use ($hash): bool {
            if (!isset($shares[$hash])) return false;
            unset($shares[$hash]);
            return true;
        });
    }

    public function updateShare(string $hash, array $data): void
    {
        AtomicFileStore::transaction($this->sharesFile, function (array &$shares) use ($hash, $data): void {
            if (isset($shares[$hash])) {
                $shares[$hash] = array_merge($shares[$hash], $data);
            }
        });
    }

    public function getAllShares(): array
    {
        return $this->getShares();
    }

    /**
     * Processes cleanup of expired shares and sends warnings.
     * Returns an array with stats: ['expired' => int, 'warned' => int]
     */
    public function processCleanup(): array
    {
        $settingsService = new SettingsService();
        return AtomicFileStore::transaction($this->sharesFile, function (array &$shares) use ($settingsService): array {
            $now = time();
            $expired = 0;
            $warned = 0;

            foreach ($shares as $hash => $share) {
            // 1. Check Expiration
            if (!empty($share['expires_at']) && $now >= (int)$share['expires_at']) {
                // If it is a transfer, delete physical files
                if (isset($share['source']) && $share['source'] === 'transfer') {
                    try {
                        $root = rtrim(config('Storage')->uploads, '/\\') . DIRECTORY_SEPARATOR . 'shares';
                        $dir = (new LocalAdapter($root))->resolvePath((string)($share['path'] ?? ''));
                        if (is_dir($dir)) {
                            $this->rrmdir($dir);
                        }
                    } catch (\Throwable $e) {
                        log_message('error', 'Unable to clean up expired transfer share: ' . $e->getMessage());
                    }
                }
                unset($shares[$hash]);
                $expired++;
                continue;
            }

            // 2. Check Warnings (If 50% of life passed and 0 downloads)
            if (
                isset($share['created_at'], $share['expires_at'], $share['sender_email'])
                && empty($share['warning_sent'])
                && filter_var($share['sender_email'], FILTER_VALIDATE_EMAIL)
            ) {
                $life = (int)$share['expires_at'] - (int)$share['created_at'];
                if ($life <= 0) {
                    continue;
                }

                $age = $now - (int)$share['created_at'];
                $downloads = (int)($share['downloads'] ?? 0);

                if ($downloads === 0 && $age > ($life / 2)) {
                    $email = \Config\Services::email();
                    $settings = $settingsService->getSettings();
                    $subject = (string)($share['subject'] ?? 'your transfer');

                    $email->setFrom($settings['email_from'], $settings['email_from_name']);
                    $email->setTo($share['sender_email']);
                    $email->setSubject("Your files haven't been downloaded yet");
                    $email->setMessage(
                        '<h2>Reminder</h2><p>The files you sent with subject <strong>'
                        . esc($subject)
                        . '</strong> have not been downloaded yet.</p>'
                    );

                    if ($email->send()) {
                        $shares[$hash]['warning_sent'] = true;
                        $warned++;
                    }
                }
            }
            }

            return ['expired' => $expired, 'warned' => $warned];
        });
    }

    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Lists shares created by a specific user.
     */
    public function listUserShares(string $user): array
    {
        $shares = $this->getShares();
        return array_values(array_filter($shares, fn($s) => $s['created_by'] === $user));
    }

    /**
     * Verifies the password for a share.
     */
    public function verifyPassword(string $hash, string $password): bool
    {
        $share = $this->getShare($hash);
        if (!$share) {
            return false;
        }
        if (!$share['password_hash']) {
            return true; // No password needed
        }

        return password_verify($password, $share['password_hash']);
    }
}
