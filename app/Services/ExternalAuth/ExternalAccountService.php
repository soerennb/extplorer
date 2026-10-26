<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use App\Services\AtomicFileStore;
use App\Services\AuthorizationState;
use RuntimeException;

/** Identity lookup and provisioning share the existing account/role/group transaction. */
final class ExternalAccountService
{
    public function synchronize(string $providerId, array $provider, ExternalIdentity $identity): array
    {
        if ($identity->issuer !== $provider['issuer']) throw new RuntimeException('External identity issuer mismatch.');
        $groups = [];
        $admitted = false;
        foreach ($identity->groups as $externalGroup) {
            if (array_key_exists($externalGroup, $provider['group_map'])) {
                $admitted = true;
                $groups = array_merge($groups, $provider['group_map'][$externalGroup]);
            }
        }
        if (!$admitted) throw new RuntimeException('External identity is not admitted.');
        $groups = array_values(array_unique($groups));
        sort($groups);
        $directory = config('Storage')->state;
        $usersPath = $directory . '/users.php';
        $rolesPath = $directory . '/roles.php';
        $groupsPath = $directory . '/groups.php';
        return AtomicFileStore::transactionMany([$usersPath => [], $rolesPath => AuthorizationState::DEFAULT_ROLES, $groupsPath => []],
            function (array &$documents) use ($providerId, $provider, $identity, $groups, $usersPath, $rolesPath, $groupsPath): array {
                $roles = $documents[$rolesPath] ?: AuthorizationState::DEFAULT_ROLES;
                if (!isset($roles[$provider['base_role']])) throw new RuntimeException('External base role is unavailable.');
                foreach ($groups as $group) if (!isset($documents[$groupsPath][$group])) throw new RuntimeException('External mapped group is unavailable.');
                $key = $identity->key($providerId);
                foreach ($documents[$usersPath] as &$user) {
                    if (($user['external_identity'] ?? null) !== $key) continue;
                    if (($user['auth_source'] ?? '') !== 'external' || !empty($user['disabled'])) throw new RuntimeException('External account is unavailable.');
                    if ($user['role'] !== $provider['base_role'] || ($user['groups'] ?? []) !== $groups) {
                        $user['auth_version'] = max(1, (int)($user['auth_version'] ?? 1)) + 1;
                    }
                    $user['role'] = $provider['base_role'];
                    $user['groups'] = $groups;
                    $user['display_name'] = $identity->displayName;
                    return $user;
                }
                unset($user);
                $username = 'ext_' . bin2hex(random_bytes(16));
                $user = [
                    'username' => $username, 'display_name' => $identity->displayName,
                    'auth_source' => 'external', 'external_identity' => $key, 'password_hash' => null,
                    'role' => $provider['base_role'], 'groups' => $groups,
                    'home_dir' => '/' . $provider['home_prefix'] . '/' . $username,
                    'allowed_extensions' => '', 'blocked_extensions' => '',
                    'auth_version' => 1, 'disabled' => false, 'must_change_password' => false,
                    '2fa_enabled' => false, '2fa_secret' => null, 'recovery_codes' => [],
                    'failed_login_count' => 0, 'locked_until' => 0,
                ];
                $documents[$usersPath][] = $user;
                return $user;
            });
    }
}
