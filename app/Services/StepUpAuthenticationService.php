<?php

namespace App\Services;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use RuntimeException;

/**
 * Provides a short-lived re-authentication grant for sensitive API mutations.
 * The grant is bound to the current user and session and is reusable for all
 * supported sensitive actions until it expires.
 */
final class StepUpAuthenticationService
{
    public const TTL_SECONDS = 600;

    /** @var list<string> */
    public const ACTIONS = [
        'user.create',
        'user.update',
        'user.delete',
        'role.save',
        'role.delete',
        'group.save',
        'group.delete',
        'settings.update',
        'mount.create',
        'mount.update',
        'mount.delete',
        'mount.test',
        'webdav-credential.create',
        'webdav-credential.delete',
    ];

    public function __construct(private ?UserModel $userModel = null)
    {
        $this->userModel ??= new UserModel();
    }

    public function issue(string $action, string $password, string $code = ''): string
    {
        $username = (string) session('username');
        if (!in_array($action, self::ACTIONS, true)
            || $username === ''
            || !session('isLoggedIn')
        ) {
            throw new RuntimeException('Re-authentication is required.');
        }

        $user = $password === '' ? null : $this->userModel->verifyUser($username, $password);
        if (!is_array($user)) {
            throw new RuntimeException('Re-authentication failed.');
        }

        if (!empty($user['2fa_enabled'])) {
            $secret = $this->userModel->get2faSecret($username);
            if ($secret === null || !(new TwoFactorService())->verifyCode($secret, trim($code))) {
                throw new RuntimeException('Two-factor authentication failed.');
            }
        }

        $token = bin2hex(random_bytes(32));
        $now = time();
        self::clearGrant();
        session()->set('step_up_grant', [
            'token_hash' => hash('sha256', $token),
            'username' => $username,
            'auth_version' => (int)($user['auth_version'] ?? 1),
            'issued_at' => $now,
            'expires_at' => $now + self::TTL_SECONDS,
        ]);

        return $token;
    }

    public function consume(RequestInterface $request, string $action): bool
    {
        if (!in_array($action, self::ACTIONS, true) || !session('isLoggedIn')) {
            return false;
        }

        $grant = $this->activeGrant();
        if ($grant === null) {
            return false;
        }

        $token = trim($request->getHeaderLine('X-Extplorer-Step-Up'));
        if ($token !== '') {
            if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)
                || !hash_equals($grant['token_hash'], hash('sha256', $token))
            ) {
                return false;
            }
        }

        $username = (string) session('username');
        if (!hash_equals($grant['username'], $username)) {
            self::clearGrant();
            return false;
        }

        $sessionVersion = (int) session('auth_version');
        if ($sessionVersion > 0 && $sessionVersion !== $grant['auth_version']) {
            self::clearGrant();
            return false;
        }

        $user = $this->userModel->getUser($username);
        if (!is_array($user)
            || !empty($user['disabled'])
            || (int)($user['auth_version'] ?? 1) !== $grant['auth_version']
        ) {
            self::clearGrant();
            return false;
        }

        return true;
    }

    public static function clearGrant(): void
    {
        session()->remove(['step_up_grant', 'step_up_challenges']);
    }

    /** @return array{token_hash: string, username: string, auth_version: int, issued_at: int, expires_at: int}|null */
    private function activeGrant(): ?array
    {
        $grant = session('step_up_grant');
        $now = time();

        if (!is_array($grant)
            || !is_string($grant['token_hash'] ?? null)
            || !preg_match('/\A[a-f0-9]{64}\z/', $grant['token_hash'])
            || !is_string($grant['username'] ?? null)
            || $grant['username'] === ''
            || (int)($grant['auth_version'] ?? 0) < 1
            || (int)($grant['issued_at'] ?? 0) < 1
            || (int)$grant['issued_at'] > $now
            || (int)($grant['expires_at'] ?? 0) <= $now
            || (int)$grant['issued_at'] > (int)$grant['expires_at']
        ) {
            self::clearGrant();
            return null;
        }

        return [
            'token_hash' => $grant['token_hash'],
            'username' => $grant['username'],
            'auth_version' => (int)$grant['auth_version'],
            'issued_at' => (int)$grant['issued_at'],
            'expires_at' => (int)$grant['expires_at'],
        ];
    }
}
