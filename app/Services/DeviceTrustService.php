<?php

namespace App\Services;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Device cookies for password-guessing protection (OWASP "device cookies").
 *
 * A browser that completed a password login for an account is trusted for
 * that account: an account-wide lock caused by anonymous failures does not
 * apply to it. Trusted devices have their own, independent failure limit.
 * Trust never replaces the password; it only selects which limit applies.
 */
class DeviceTrustService
{
    public const COOKIE_NAME = 'extplorer_device';
    public const TTL = 15_552_000; // 180 days
    public const MAX_FAILURES = 5;
    public const LOCK_SECONDS = 900;
    private const MAX_DEVICES_PER_USER = 20;

    private string $devicesFile;

    public function __construct(?string $devicesFile = null)
    {
        $this->devicesFile = $devicesFile ?? config('Storage')->state . '/trusted_devices.php';
    }

    public function trust(string $username, ?ResponseInterface $response = null, ?RequestInterface $request = null): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        [$previous] = $this->splitCookie($this->readCookie($request) ?? '');
        $now = time();

        AtomicFileStore::transaction($this->devicesFile, function (array &$devices) use ($username, $selector, $validator, $previous, $now): void {
            unset($devices[$previous]);
            $this->prune($devices, $now);
            $owned = array_keys(array_filter($devices, static fn($entry): bool => ($entry['username'] ?? null) === $username));
            usort($owned, static fn($a, $b): int => (int)$devices[$a]['created_at'] <=> (int)$devices[$b]['created_at']);
            foreach (array_slice($owned, 0, max(0, count($owned) - self::MAX_DEVICES_PER_USER + 1)) as $oldest) {
                unset($devices[$oldest]);
            }
            $devices[$selector] = [
                'username' => $username,
                'validator_hash' => hash('sha256', $validator),
                'created_at' => $now,
                'expires_at' => $now + self::TTL,
                'failures' => 0,
                'locked_until' => 0,
            ];
        });

        ($response ?? Services::response())->setCookie(
            self::COOKIE_NAME, $selector . ':' . $validator, self::TTL, '', '/', '', $this->isSecureRequest(), true, 'Lax'
        );
    }

    public function isTrusted(?RequestInterface $request, string $username): bool
    {
        $selector = $this->matchingSelector($request, $username);
        if ($selector === null) {
            return false;
        }

        $entry = AtomicFileStore::read($this->devicesFile)[$selector] ?? null;
        return is_array($entry) && (int)($entry['locked_until'] ?? 0) <= time();
    }

    public function recordFailure(?RequestInterface $request, string $username): void
    {
        $this->update($request, $username, static function (array &$entry): void {
            $entry['failures'] = (int)($entry['failures'] ?? 0) + 1;
            if ($entry['failures'] >= self::MAX_FAILURES) {
                $entry['failures'] = 0;
                $entry['locked_until'] = time() + self::LOCK_SECONDS;
            }
        });
    }

    public function recordSuccess(?RequestInterface $request, string $username): void
    {
        $this->update($request, $username, static function (array &$entry): void {
            $entry['failures'] = 0;
            $entry['locked_until'] = 0;
        });
    }

    private function update(?RequestInterface $request, string $username, callable $change): void
    {
        $selector = $this->matchingSelector($request, $username);
        if ($selector === null) {
            return;
        }

        AtomicFileStore::transaction($this->devicesFile, function (array &$devices) use ($selector, $change): void {
            if (isset($devices[$selector]) && is_array($devices[$selector])) {
                $change($devices[$selector]);
            }
        });
    }

    /** The cookie's device entry when it is valid, unexpired and bound to this account. */
    private function matchingSelector(?RequestInterface $request, string $username): ?string
    {
        [$selector, $validator] = $this->splitCookie($this->readCookie($request) ?? '');
        if ($username === '' || preg_match('/\A[a-f0-9]{24}\z/', $selector) !== 1 || preg_match('/\A[a-f0-9]{64}\z/', $validator) !== 1) {
            return null;
        }

        $entry = AtomicFileStore::read($this->devicesFile)[$selector] ?? null;
        if (!is_array($entry)
            || !hash_equals((string)($entry['validator_hash'] ?? ''), hash('sha256', $validator))
            || !hash_equals((string)($entry['username'] ?? ''), $username)
            || (int)($entry['expires_at'] ?? 0) <= time()) {
            return null;
        }

        return $selector;
    }

    private function prune(array &$devices, int $now): void
    {
        foreach ($devices as $selector => $entry) {
            if (!is_array($entry) || (int)($entry['expires_at'] ?? 0) <= $now) {
                unset($devices[$selector]);
            }
        }
    }

    private function readCookie(?RequestInterface $request): ?string
    {
        if ($request && method_exists($request, 'getCookie')) {
            $value = $request->getCookie(self::COOKIE_NAME);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $value = $_COOKIE[self::COOKIE_NAME] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return array{0: string, 1: string} */
    private function splitCookie(string $cookie): array
    {
        $parts = explode(':', $cookie, 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    private function isSecureRequest(): bool
    {
        try {
            return Services::request()->isSecure();
        } catch (\Throwable) {
            return !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
        }
    }
}
