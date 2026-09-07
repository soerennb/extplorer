<?php

namespace App\Services;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class RememberMeService
{
    public const COOKIE_NAME = 'extplorer_remember';
    public const TOKEN_TTL = 2_592_000; // 30 days

    private string $tokensFile;
    private UserModel $userModel;

    public function __construct(?UserModel $userModel = null, ?string $tokensFile = null)
    {
        $this->userModel = $userModel ?? new UserModel();
        $this->tokensFile = $tokensFile ?? config('Storage')->state . '/remember_tokens.php';

        if (!is_file($this->tokensFile)) {
            $this->saveTokens([]);
        }
    }

    public function remember(string $username, ?ResponseInterface $response = null): void
    {
        $user = $this->userModel->getUser($username);
        if (!$user || !empty($user['disabled']) || (int)($user['locked_until'] ?? 0) > time()) {
            return;
        }

        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $now = time();

        AtomicFileStore::transaction($this->tokensFile, function (array &$tokens) use ($username, $selector, $validator, $now, $user): void {
            foreach ($tokens as $existingSelector => $entry) {
                if (($entry['username'] ?? null) === $username) {
                    unset($tokens[$existingSelector]);
                }
            }
            $tokens[$selector] = [
                'username' => $username,
                'auth_version' => (int)($user['auth_version'] ?? 1),
                'validator_hash' => $this->hashValidator($validator),
                'created_at' => $now,
                'last_used_at' => null,
                'expires_at' => $now + self::TOKEN_TTL,
            ];
        });

        $this->setCookie($selector . ':' . $validator, $response);
    }

    public function forget(?RequestInterface $request = null, ?ResponseInterface $response = null): void
    {
        $cookie = $this->readCookie($request);
        if ($cookie !== null) {
            [$selector] = $this->splitCookie($cookie);
            if ($selector !== '') {
                AtomicFileStore::transaction($this->tokensFile, function (array &$tokens) use ($selector): void {
                    unset($tokens[$selector]);
                });
            }
        }

        $this->clearCookie($response);
    }

    public function restore(RequestInterface $request, ?ResponseInterface $response = null): bool
    {
        $cookie = $this->readCookie($request);
        if ($cookie === null) {
            return false;
        }

        [$selector, $validator] = $this->splitCookie($cookie);
        if ($selector === '' || $validator === '') {
            $this->clearCookie($response);
            return false;
        }

        $entry = AtomicFileStore::transaction($this->tokensFile, function (array &$tokens) use ($selector, $validator): ?array {
            $entry = $tokens[$selector] ?? null;
            if (!is_array($entry)) {
                return null;
            }

            unset($tokens[$selector]);
            if ((int)($entry['expires_at'] ?? 0) <= time()) {
                return null;
            }

            $expected = (string)($entry['validator_hash'] ?? '');
            if ($expected === '' || !hash_equals($expected, $this->hashValidator($validator))) {
                return null;
            }

            return $entry;
        }, []);
        if (!is_array($entry)) {
            $this->clearCookie($response);
            return false;
        }

        $user = $this->userModel->getUser((string)($entry['username'] ?? ''));
        if (!$user || !empty($user['disabled']) || (int)($user['locked_until'] ?? 0) > time()) {
            $this->clearCookie($response);
            return false;
        }

        $tokenVersion = (int)($entry['auth_version'] ?? 0);
        if ($tokenVersion > 0 && $tokenVersion !== (int)($user['auth_version'] ?? 1)) {
            $this->clearCookie($response);
            return false;
        }

        (new AuthenticationService($this->userModel))->startLocalSession($user, true);
        $this->remember($user['username'], $response);

        return true;
    }

    public function pruneExpired(): void
    {
        AtomicFileStore::transaction($this->tokensFile, function (array &$tokens): void {
            $this->pruneTokenArray($tokens);
        });
    }

    public function revokeUser(string $username): void
    {
        AtomicFileStore::transaction($this->tokensFile, function (array &$tokens) use ($username): void {
            foreach ($tokens as $selector => $entry) {
                if (($entry['username'] ?? null) === $username) {
                    unset($tokens[$selector]);
                }
            }
        });
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

    private function splitCookie(string $cookie): array
    {
        $parts = explode(':', $cookie, 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private function hashValidator(string $validator): string
    {
        return hash('sha256', $validator);
    }

    private function setCookie(string $value, ?ResponseInterface $response = null): void
    {
        ($response ?? Services::response())->setCookie(
            self::COOKIE_NAME,
            $value,
            self::TOKEN_TTL,
            '',
            '/',
            '',
            $this->isSecureRequest(),
            true,
            'Lax'
        );
    }

    private function clearCookie(?ResponseInterface $response = null): void
    {
        ($response ?? Services::response())->setCookie(
            self::COOKIE_NAME,
            '',
            -3600,
            '',
            '/',
            '',
            $this->isSecureRequest(),
            true,
            'Lax'
        );
    }

    private function isSecureRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    private function loadTokens(): array
    {
        return AtomicFileStore::read($this->tokensFile);
    }

    private function saveTokens(array $tokens): void
    {
        $this->pruneTokenArray($tokens);
        AtomicFileStore::write($this->tokensFile, $tokens);
    }

    private function pruneTokenArray(array &$tokens): void
    {
        $now = time();
        foreach ($tokens as $selector => $entry) {
            if (!is_array($entry) || (int)($entry['expires_at'] ?? 0) <= $now) {
                unset($tokens[$selector]);
            }
        }
    }
}
