<?php

namespace App\Services;

use App\Models\UserModel;
use App\Services\VFS\LocalAdapter;
use App\Services\VFS\PathPolicy;
use Exception;

class ShareService
{
    private string $sharesFile;
    private object $storage;

    public function __construct(?string $sharesFile = null)
    {
        $this->storage = config('Storage');
        $this->sharesFile = $sharesFile ?? ($this->storage->state . '/shares.php');

        if (!AtomicFileStore::exists($this->sharesFile)) {
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

        $targetPath = rtrim($this->storage->fileManagerRoot, '/\\') . DIRECTORY_SEPARATOR . $path;
        try {
            [, $targetPath] = $this->resolveSharePaths([
                'path' => $path,
                'source' => (string)($meta['source'] ?? ''),
                'created_by' => $user,
            ]);
        } catch (\Throwable) {
            // Keep creation compatible with legacy records whose owner no
            // longer exists; public access will fail closed for such records.
        }
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
        if (!$this->isValidHash($hash)) {
            return null;
        }
        $shares = $this->getShares();
        return $shares[$hash] ?? null;
    }

    public function isValidHash(string $hash): bool
    {
        return preg_match('/\A[a-f0-9]{32}\z/D', $hash) === 1;
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
                        $dir = $this->resolveTransferDirectory((string)($share['path'] ?? ''), true);
                        if (is_dir($dir) && !is_link($dir)) {
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
        if (is_dir($dir) && !is_link($dir)) {
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
     * Resolve a transfer share directory without accepting arbitrary persisted
     * paths. Transfer directories are generated opaque identifiers, not user
     * supplied relative paths.
     */
    public function resolveTransferDirectory(string $relativePath, bool $mustExist = false): string
    {
        if (preg_match('/\A[a-f0-9]{16}\z/D', $relativePath) !== 1) {
            throw new \RuntimeException('Invalid transfer storage.');
        }

        $root = rtrim($this->storage->uploads, '/\\') . DIRECTORY_SEPARATOR . 'shares';
        $this->assertNoSymlinkComponents($root);
        if (is_link($root)) {
            throw new \RuntimeException('Transfer storage uses a symbolic link.');
        }
        if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) {
            throw new \RuntimeException('Unable to create transfer storage.');
        }
        $this->assertNoSymlinkComponents($root);
        $rootReal = realpath($root);
        if ($rootReal === false || !is_dir($rootReal)) {
            throw new \RuntimeException('Invalid transfer storage.');
        }

        $candidate = rtrim($rootReal, '/\\') . DIRECTORY_SEPARATOR . $relativePath;
        if (is_link($candidate)) {
            throw new \RuntimeException('Transfer storage uses a symbolic link.');
        }
        if (file_exists($candidate)) {
            $real = realpath($candidate);
            if ($real === false || !is_dir($real) || !PathPolicy::isWithinRoot($rootReal, $real)) {
                throw new \RuntimeException('Invalid transfer storage.');
            }
            return $real;
        }
        if ($mustExist) {
            throw new \RuntimeException('Transfer storage was not found.');
        }

        return $candidate;
    }

    /**
     * Resolve a persisted share to its intended local root and target. The
     * public URL must follow the creator's virtual Home/Shared mount mapping;
     * concatenating the virtual path below fileManagerRoot would otherwise
     * point at a different user's data or an unrelated directory.
     *
     * @return array{0:string,1:string}
     */
    public function resolveSharePaths(array $share): array
    {
        $path = PathPolicy::normalizeRelative((string)($share['path'] ?? ''));
        if ($path === '') {
            throw new \RuntimeException('Share path is invalid.');
        }
        if (($share['source'] ?? '') === 'transfer') {
            $root = rtrim($this->storage->uploads, '/\\') . DIRECTORY_SEPARATOR . 'shares';
            return [$root, $this->resolveTransferDirectory($path)];
        }

        $parts = explode('/', $path, 2);
        $mount = $parts[0];
        $relative = $parts[1] ?? '';
        $root = $this->storage->fileManagerRoot;

        if ($mount === 'Home') {
            $owner = (string)($share['created_by'] ?? '');
            $user = $owner !== '' ? (new UserModel())->getUser($owner) : null;
            if (!is_array($user)) {
                throw new \RuntimeException('Share owner was not found.');
            }
            $root = (new LocalAdapter($root))->resolvePath((string)($user['home_dir'] ?? '/'));
        } elseif ($mount === 'Shared') {
            $root = $this->storage->shared;
        } else {
            // Compatibility for pre-virtual-namespace share records.
            $relative = $path;
        }

        $resolver = new LocalAdapter($root);
        return [$root, $resolver->resolvePath($relative)];
    }

    private function assertNoSymlinkComponents(string $path): void
    {
        $current = rtrim($path, '/\\');
        while ($current !== dirname($current)) {
            if (is_link($current)) {
                throw new \RuntimeException('Transfer storage uses a symbolic link.');
            }
            $current = dirname($current);
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
     * Projection for authenticated share-management clients.
     * Password hashes and transfer message/recipient metadata never leave the
     * server through this view.
     *
     * @param array<string, mixed> $share
     * @return array<string, mixed>
     */
    public function ownerView(array $share): array
    {
        $fields = [
            'hash', 'path', 'type', 'created_at', 'expires_at', 'mode',
            'downloads', 'source', 'is_transfer', 'file_count', 'total_size',
        ];
        $view = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $share)) {
                $view[$field] = $share[$field];
            }
        }
        $view['password_protected'] = !empty($share['password_hash']);

        return $view;
    }

    /**
     * Projection for an unauthenticated/public share page.
     *
     * @param array<string, mixed> $share
     * @return array<string, mixed>
     */
    public function publicView(array $share): array
    {
        return [
            'hash' => (string)($share['hash'] ?? ''),
            'type' => (string)($share['type'] ?? 'dir'),
            'subject' => (string)($share['subject'] ?? ''),
            'mode' => (string)($share['mode'] ?? 'read'),
            'expires_at' => $share['expires_at'] ?? null,
            'source' => (string)($share['source'] ?? ''),
            'display_name' => basename((string)($share['path'] ?? '')),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function listUserShareViews(string $user): array
    {
        return array_map(fn(array $share): array => $this->ownerView($share), $this->listUserShares($user));
    }

    /**
     * Minimal authenticated transfer history projection. Recipient addresses,
     * message text, sender identity and storage paths are intentionally not
     * returned by the history endpoint.
     *
     * @param array<string, mixed> $share
     * @return array<string, mixed>
     */
    public function transferView(array $share): array
    {
        return [
            'hash' => (string)($share['hash'] ?? ''),
            'subject' => (string)($share['subject'] ?? ''),
            'created_at' => (int)($share['created_at'] ?? 0),
            'expires_at' => $share['expires_at'] ?? null,
            'downloads' => (int)($share['downloads'] ?? 0),
            'file_count' => (int)($share['file_count'] ?? 0),
            'total_size' => (int)($share['total_size'] ?? 0),
            'recipient_count' => is_array($share['recipients'] ?? null)
                ? count($share['recipients'])
                : 0,
        ];
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
