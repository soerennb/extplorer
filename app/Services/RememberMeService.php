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
        $this->tokensFile = $tokensFile ?? WRITEPATH . 'remember_tokens.php';

        if (!is_file($this->tokensFile)) {
            $this->saveTokens([]);
        }
    }

    public function remember(string $username, ?ResponseInterface $response = null): void
    {
        $this->removeUserTokens($username);

        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $now = time();

        $tokens = $this->loadTokens();
        $tokens[$selector] = [
            'username' => $username,
            'validator_hash' => $this->hashValidator($validator),
            'created_at' => $now,
            'last_used_at' => null,
            'expires_at' => $now + self::TOKEN_TTL,
        ];
        $this->saveTokens($tokens);

        $this->setCookie($selector . ':' . $validator, $response);
    }

    public function forget(?RequestInterface $request = null, ?ResponseInterface $response = null): void
    {
        $cookie = $this->readCookie($request);
        if ($cookie !== null) {
            [$selector] = $this->splitCookie($cookie);
            if ($selector !== '') {
                $tokens = $this->loadTokens();
                unset($tokens[$selector]);
                $this->saveTokens($tokens);
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

        $tokens = $this->loadTokens();
        $entry = $tokens[$selector] ?? null;
        if (!is_array($entry)) {
            $this->clearCookie($response);
            return false;
        }

        if ((int)($entry['expires_at'] ?? 0) < time()) {
            unset($tokens[$selector]);
            $this->saveTokens($tokens);
            $this->clearCookie($response);
            return false;
        }

        $expected = (string)($entry['validator_hash'] ?? '');
        if ($expected === '' || !hash_equals($expected, $this->hashValidator($validator))) {
            unset($tokens[$selector]);
            $this->saveTokens($tokens);
            $this->clearCookie($response);
            return false;
        }

        $user = $this->userModel->getUser((string)($entry['username'] ?? ''));
        if (!$user) {
            unset($tokens[$selector]);
            $this->saveTokens($tokens);
            $this->clearCookie($response);
            return false;
        }

        unset($tokens[$selector]);
        $this->saveTokens($tokens);
        $this->startLocalSession($user);
        $this->remember($user['username'], $response);

        return true;
    }

    public function pruneExpired(): void
    {
        $now = time();
        $tokens = array_filter(
            $this->loadTokens(),
            static fn(array $entry): bool => (int)($entry['expires_at'] ?? 0) >= $now
        );
        $this->saveTokens($tokens);
    }

    private function startLocalSession(array $user): void
    {
        session()->regenerate();
        session()->set([
            'isLoggedIn' => true,
            'username' => $user['username'],
            'role' => $user['role'],
            'home_dir' => $user['home_dir'],
            'allowed_extensions' => $user['allowed_extensions'] ?? '',
            'blocked_extensions' => $user['blocked_extensions'] ?? '',
            'permissions' => $this->userModel->getPermissions($user['username']),
            'connection' => ['mode' => 'local'],
            'force_password_change' => ($user['username'] === 'admin' && password_verify('admin', $user['password_hash'])),
            'remembered_login' => true,
            'last_activity_ts' => time(),
        ]);
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

    private function removeUserTokens(string $username): void
    {
        $tokens = array_filter(
            $this->loadTokens(),
            static fn(array $entry): bool => ($entry['username'] ?? null) !== $username
        );
        $this->saveTokens($tokens);
    }

    private function loadTokens(): array
    {
        if (!is_file($this->tokensFile)) {
            return [];
        }

        $content = file_get_contents($this->tokensFile);
        if ($content === false) {
            return [];
        }

        if (str_starts_with($content, '<?php')) {
            $content = substr($content, (int)strpos($content, "\n") + 1);
        }

        $tokens = json_decode($content, true);
        return is_array($tokens) ? $tokens : [];
    }

    private function saveTokens(array $tokens): void
    {
        $this->pruneTokenArray($tokens);
        $content = '<?php die("Access denied"); ?>' . PHP_EOL . json_encode($tokens, JSON_PRETTY_PRINT);
        file_put_contents($this->tokensFile, $content, LOCK_EX);
    }

    private function pruneTokenArray(array &$tokens): void
    {
        $now = time();
        foreach ($tokens as $selector => $entry) {
            if (!is_array($entry) || (int)($entry['expires_at'] ?? 0) < $now) {
                unset($tokens[$selector]);
            }
        }
    }
}
