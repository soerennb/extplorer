<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use RuntimeException;

/** Identity lookup and provisioning share the existing account/role/group transaction. */
final class ExternalAccountService
{
    public function synchronize(string $providerId, array $provider, ExternalIdentity $identity, ?int $cursor = null): array
    {
        if ($identity->issuer !== $provider['issuer']) throw new RuntimeException('External identity issuer mismatch.');
        $result = ExternalState::transaction(function (array &$documents) use ($providerId, $provider, $identity, $cursor): array {
            $state = &$documents['external_auth'];
            $key = $identity->key($providerId);
            $identityKey = ExternalState::identityKey($key);
            $lifecycle = $state['identities'][$identityKey] ?? [];
            $barrier = max($lifecycle['changed_sequence'] ?? 0, $state['provider_changes'][$providerId] ?? 0);
            if (!empty($lifecycle['suspended']) || ($cursor !== null && $barrier > $cursor)) {
                throw new RuntimeException('External identity is suspended or changed while signing in.');
            }
            $mapping = ExternalGroupMapping::resolve($provider, $identity->groups, $documents['roles'], $documents['groups']);
            $groups = $mapping['groups'];
            if (!$mapping['admitted']) {
                $state['sequence']++;
                $state['identities'][$identityKey] = array_replace($lifecycle, ['admitted' => false, 'changed_sequence' => $state['sequence']]);
                foreach ($documents['users'] as &$account) {
                    if (($account['external_identity'] ?? null) === $key) $account['auth_version'] = (int)($account['auth_version'] ?? 1) + 1;
                }
                unset($account);
                return ['denied' => true];
            }
            $state['identities'][$identityKey] = array_replace($lifecycle, ['admitted' => true]);
            foreach ($documents['users'] as &$user) {
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
            $documents['users'][] = $user;
            return $user;
        });
        if (isset($result['denied'])) throw new RuntimeException('External identity is not admitted.');
        return $result;
    }
}
