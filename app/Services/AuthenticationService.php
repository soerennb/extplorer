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

    public function startLocalSession(array $user, bool $remembered = false): bool
    {
        $snapshot = $this->userModel->authorizationSnapshot((string)$user['username']);
        $current = $snapshot['user'] ?? null;
        if ($current === null || !empty($current['disabled']) || (int)($current['locked_until'] ?? 0) > time()
            || (int)($current['auth_version'] ?? 1) !== (int)($user['auth_version'] ?? 1)
            || ($current['password_hash'] ?? null) !== ($user['password_hash'] ?? null)) return false;
        $user = $current;
        StepUpAuthenticationService::clearGrant();
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
        session()->regenerate();
        session()->set([
            'isLoggedIn' => true,
            'username' => $username,
            'role' => 'user',
            'home_dir' => '/',
            'permissions' => ['read', 'write', 'upload', 'delete', 'chmod'],
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
