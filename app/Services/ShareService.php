<?php

namespace App\Services;

use Exception;

class ShareService
{
    private string $sharesFile;

    public function __construct(?string $sharesFile = null)
    {
        $this->sharesFile = $sharesFile ?? (WRITEPATH . 'shares.json');
        if (!file_exists($this->sharesFile)) {
            file_put_contents($this->sharesFile, json_encode([]));
        }
    }

    private function getShares(): array
    {
        return json_decode(file_get_contents($this->sharesFile), true) ?? [];
    }

    private function saveShares(array $shares): void
    {
        file_put_contents($this->sharesFile, json_encode($shares, JSON_PRETTY_PRINT));
    }

    /**
     * Creates a new share link.
     */
    public function createShare(string $path, string $user, ?string $password = null, ?int $expiresAt = null, string $mode = 'read'): array
    {
        $shares = $this->getShares();
        
        // Generate a random hash (16 chars)
        $hash = bin2hex(random_bytes(8));
        while (isset($shares[$hash])) {
            $hash = bin2hex(random_bytes(8));
        }

        $share = [
            'hash' => $hash,
            'path' => $path,
            'type' => is_dir(WRITEPATH . 'file_manager_root/' . $path) ? 'dir' : 'file', // Assuming root logic
            'created_by' => $user,
            'created_at' => time(),
            'expires_at' => $expiresAt,
            'password_hash' => $password ? password_hash($password, PASSWORD_DEFAULT) : null,
            'mode' => $mode,
            'downloads' => 0
        ];

        $shares[$hash] = $share;
        $this->saveShares($shares);

        return $share;
    }

    /**
     * Retrieves a share by hash, validating expiration.
     */
    public function getShare(string $hash): ?array
    {
        $shares = $this->getShares();
        if (!isset($shares[$hash])) return null;

        $share = $shares[$hash];

        // Check Expiration
        if ($share['expires_at'] && time() > $share['expires_at']) {
            return null; // Or return 'expired' status if logic demands
        }

        return $share;
    }

    /**
     * Deletes a share.
     */
    public function deleteShare(string $hash): bool
    {
        $shares = $this->getShares();
        if (!isset($shares[$hash])) return false;

        unset($shares[$hash]);
        $this->saveShares($shares);
        return true;
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
        if (!$share || !$share['password_hash']) return true; // No password needed

        return password_verify($password, $share['password_hash']);
    }
}
