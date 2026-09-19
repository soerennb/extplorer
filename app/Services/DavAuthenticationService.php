<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserModel;

final class DavAuthenticationService
{
    public function __construct(
        private ?UserModel $users = null,
        private ?WebDavCredentialService $credentials = null,
    ) {
        $this->users ??= new UserModel();
        $this->credentials ??= new WebDavCredentialService();
    }

    /** @return array<string,mixed>|null */
    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->users->getUser($username);
        if (!is_array($user) || !empty($user['disabled']) || (int)($user['locked_until'] ?? 0) > time()) {
            return null;
        }

        if ($this->credentials->verify((string)$user['username'], $password)) {
            return $user;
        }

        if (!empty($user['2fa_enabled'])) {
            return null;
        }

        return $this->users->verifyUser($username, $password);
    }
}
