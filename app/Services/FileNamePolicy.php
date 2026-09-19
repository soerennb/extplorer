<?php

namespace App\Services;

use RuntimeException;

/**
 * Security policy for names that can be introduced into a writable tree.
 *
 * This policy is intentionally independent from per-user allowlists. An
 * allowlist may narrow accepted uploads, but it must never re-enable a name
 * that could be interpreted as server configuration or executable code by a
 * misconfigured web server.
 */
final class FileNamePolicy
{
    /** @var list<string> */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phps', 'phtml', 'phtm', 'pht', 'phar',
        'pl', 'py', 'rb', 'cgi', 'fcgi', 'asp', 'aspx', 'jsp', 'jspx',
        'exe', 'com', 'dll', 'so', 'sh', 'bash', 'zsh', 'ksh', 'bat', 'cmd', 'shtml',
    ];

    /** @var list<string> */
    private const BLOCKED_NAMES = [
        '.htaccess',
        '.htpasswd',
        '.user.ini',
        'web.config',
    ];

    public function assertSafe(string $filename): string
    {
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new RuntimeException('Invalid filename.');
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $filename) === 1) {
            throw new RuntimeException('Filename contains invalid characters.');
        }
        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new RuntimeException('Invalid filename.');
        }
        if (str_contains($filename, ':') || preg_match('/[. ]\z/', $filename) === 1) {
            throw new RuntimeException('Invalid filename.');
        }
        if (strlen($filename) > 255) {
            throw new RuntimeException('Filename is too long.');
        }

        $normalized = strtolower($filename);
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        // Windows treats these device names specially, even when an extension
        // is appended. Rejecting them keeps the policy consistent across local
        // and remote filesystems and avoids ambiguous archive/extraction names.
        if (preg_match('/\A(?:con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\..*)?\z/i', $filename) === 1) {
            throw new RuntimeException('This filename is not allowed for security reasons.');
        }
        if (in_array($normalized, self::BLOCKED_NAMES, true)
            || in_array($extension, self::BLOCKED_EXTENSIONS, true)
            || in_array(ltrim($normalized, '.'), self::BLOCKED_EXTENSIONS, true)) {
            throw new RuntimeException('This filename is not allowed for security reasons.');
        }

        return $filename;
    }

    /**
     * Validate every component of a root-relative path that is about to be
     * created or replaced. Traversal is handled by the filesystem path policy;
     * this method adds the dangerous-name policy to every component as well.
     */
    public function assertSafePath(string $path, bool $allowEmpty = false): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $relative = trim($normalized, '/');
        if ($relative === '') {
            if ($allowEmpty) {
                return $path;
            }
            throw new RuntimeException('A target filename is required.');
        }

        foreach (explode('/', $relative) as $component) {
            $this->assertSafe($component);
        }

        return $path;
    }

    /**
     * Applies the hard deny-list and then the optional user policy.
     *
     * @param mixed $allowed
     * @param mixed $blocked
     */
    public function isAllowed(string $filename, $allowed = null, $blocked = null): bool
    {
        try {
            $this->assertSafe($filename);
        } catch (RuntimeException) {
            return false;
        }

        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = $this->normalizeExtensions($allowed);
        if ($allowedExtensions !== [] && !in_array($extension, $allowedExtensions, true)) {
            return false;
        }

        $blockedExtensions = $this->normalizeExtensions($blocked);
        return !in_array($extension, $blockedExtensions, true);
    }

    /** @param mixed $raw @return list<string> */
    private function normalizeExtensions($raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/[\s,;]+/', $raw) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $extension) {
            $extension = strtolower(ltrim(trim((string)$extension), '.'));
            if ($extension !== '' && preg_match('/\A[a-z0-9]+\z/', $extension)) {
                $result[] = $extension;
            }
        }

        return array_values(array_unique($result));
    }
}
