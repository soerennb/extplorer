<?php

namespace App\Services;

use Exception;

class ShareService
{
    private string $sharesFile;

    public function __construct(?string $sharesFile = null)
    {
        $this->sharesFile = $sharesFile ?? (WRITEPATH . 'shares.php');
        
        // Migration
        if (file_exists(WRITEPATH . 'shares.json') && !file_exists($this->sharesFile)) {
            $data = json_decode(file_get_contents(WRITEPATH . 'shares.json'), true) ?? [];
            $this->saveShares($data);
            unlink(WRITEPATH . 'shares.json');
        }

        if (!file_exists($this->sharesFile)) {
            $this->saveShares([]);
        }
    }

    private function getShares(): array
    {
        if (!file_exists($this->sharesFile)) return [];
        $content = file_get_contents($this->sharesFile);
        if (strpos($content, '<?php') === 0) {
            $content = str_replace('<?php die("Access denied"); ?>' . PHP_EOL, '', $content);
        }
        return json_decode($content, true) ?? [];
    }

    private function saveShares(array $shares): void
    {
        $content = '<?php die("Access denied"); ?>' . PHP_EOL . json_encode($shares, JSON_PRETTY_PRINT);
        file_put_contents($this->sharesFile, $content);
    }

    /**
     * Creates a new share link.
     */
    public function createShare(string $path, string $user, ?string $password = null, ?int $expiresAt = null, string $mode = 'read', array $meta = []): array
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

        // Merge extra metadata (e.g. transfer info)
        $share = array_merge($share, $meta);

        $shares[$hash] = $share;
        $this->saveShares($shares);

        return $share;
    }

    public function incrementDownloads(string $hash): void
    {
        $shares = $this->getShares();
        if (isset($shares[$hash])) {
            $shares[$hash]['downloads'] = ($shares[$hash]['downloads'] ?? 0) + 1;
            $this->saveShares($shares);
        }
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
        if ($share['expires_at'] && time() > $share['expires_at']) {
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
        $shares = $this->getShares();
        if (!isset($shares[$hash])) return false;

        unset($shares[$hash]);
        $this->saveShares($shares);
        return true;
    }

    public function updateShare(string $hash, array $data): void
    {
        $shares = $this->getShares();
        if (isset($shares[$hash])) {
            $shares[$hash] = array_merge($shares[$hash], $data);
            $this->saveShares($shares);
        }
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
        $shares = $this->getShares();
        $now = time();
        $expired = 0;
        $warned = 0;
        $settingsService = new SettingsService();
        $emailService = new EmailService();

        foreach ($shares as $hash => $share) {
            // 1. Check Expiration
            if ($share['expires_at'] && $now > $share['expires_at']) {
                // If it is a transfer, delete physical files
                if (isset($share['source']) && $share['source'] === 'transfer') {
                    $dir = WRITEPATH . 'uploads/shares/' . $share['path'];
                    if (is_dir($dir)) {
                        $this->rrmdir($dir);
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

        if ($expired > 0 || $warned > 0) {
            $this->saveShares($shares);
        }

        return ['expired' => $expired, 'warned' => $warned];
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
