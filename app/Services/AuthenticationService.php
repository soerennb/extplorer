<?php

namespace App\Services;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Owns the session shape and the account-level token revocation boundary.
 */
final class AuthenticationService
{
    public function __construct(private ?UserModel $userModel = null)
    {
        $this->userModel ??= new UserModel();
    }

    /**
     * Verify a local password. A device that previously completed a login
     * for this account is limited by its own failure counter instead of the
     * account-wide lock that anonymous guessing can trigger.
     */
    public function verifyPassword(?RequestInterface $request, string $username, string $password, ?DeviceTrustService $devices = null): ?array
    {
        $devices ??= new DeviceTrustService();
        $trusted = $devices->isTrusted($request, $username);
        $user = $this->userModel->verifyUser($username, $password, $trusted);
        if ($trusted) {
            $user === null ? $devices->recordFailure($request, $username) : $devices->recordSuccess($request, $username);
        }

        return $user;
    }

    public function startLocalSession(array $user, bool $remembered = false): bool
    {
        if (($user['auth_source'] ?? 'local') !== 'local') return false;
        return $this->startAccountSession($user, $remembered);
    }

    public function startExternalSession(array $user, string $provider, string $fingerprint, int $ttl): bool
    {
        if (($user['auth_source'] ?? '') !== 'external' || ($user['external_identity']['provider'] ?? null) !== $provider
            || !$this->startAccountSession($user, false)) return false;
        session()->set('external_auth', [
            'provider' => $provider, 'fingerprint' => $fingerprint,
            'identity' => $user['external_identity'], 'expires_at' => time() + $ttl,
        ]);
        return true;
    }

    private function startAccountSession(array $user, bool $remembered): bool
    {
        $snapshot = $this->userModel->authorizationSnapshot((string)$user['username']);
        $current = $snapshot['user'] ?? null;
        if ($current === null || !empty($current['disabled'])
            || (int)($current['auth_version'] ?? 1) !== (int)($user['auth_version'] ?? 1)
            || ($current['password_hash'] ?? null) !== ($user['password_hash'] ?? null)) return false;
        $user = $current;
        StepUpAuthenticationService::clearGrant();
        session()->remove(['external_auth', 'external_auth_transactions']);
        session()->regenerate();
        session()->set([
            'isLoggedIn' => true,
            'username' => (string)$user['username'],
            'role' => (string)($user['role'] ?? 'user'),
            'home_dir' => (string)($user['home_dir'] ?? '/'),
            'allowed_extensions' => (string)($user['allowed_extensions'] ?? ''),
            'blocked_extensions' => (string)($user['blocked_extensions'] ?? ''),
            'permissions' => $snapshot['permissions'],
            'connection' => ['mode' => 'local'],
            'force_password_change' => !empty($user['must_change_password']),
            'auth_version' => max(1, (int)($user['auth_version'] ?? 1)),
            'remembered_login' => $remembered,
            'last_activity_ts' => time(),
        ]);
        return true;
    }

    public function startRemoteSession(string $username, array $connection): void
    {
        StepUpAuthenticationService::clearGrant();
        session()->remove(['external_auth', 'external_auth_transactions']);
        session()->regenerate();
        session()->set([
            'isLoggedIn' => true,
            'username' => $username,
            'role' => 'user',
            'home_dir' => '/',
            'allowed_extensions' => '',
            'blocked_extensions' => '',
            'auth_version' => 0,
            'force_password_change' => false,
            'permissions' => ['read', 'write', 'upload', 'delete', 'rename', 'chmod'],
            'connection' => $connection,
            'remembered_login' => false,
            'last_activity_ts' => time(),
        ]);
    }

    public function revokeUserTokens(string $username): void
    {
        (new RememberMeService($this->userModel))->revokeUser($username);
    }

    public function logout(RequestInterface $request, ResponseInterface $response): void
    {
        (new RememberMeService($this->userModel))->forget($request, $response);
        StepUpAuthenticationService::clearGrant();
        session()->remove([
            'isLoggedIn',
            'username',
            'role',
            'home_dir',
            'permissions',
            'connection',
            'auth_version',
            'force_password_change',
            'remembered_login',
            'last_activity_ts',
        ]);
        session()->destroy();
    }
}
