<?php

namespace App\Services;

use App\Services\VFS\PathPolicy;

/** Checked under the authorization-state lock, using persisted actor rights. */
final class AdminDelegationPolicy
{
    public function assertMutation(array $before, array $after, string $actorName): void
    {
        $actor = null;
        foreach ($before['users'] as $user) {
            if (($user['username'] ?? '') === $actorName) $actor = $user;
        }
        if ($actor === null || !empty($actor['disabled']) || (int)($actor['locked_until'] ?? 0) > time()) {
            throw new AdminDelegationException();
        }
        $permissions = AuthorizationState::permissions($actor, $before['roles'], $before['groups']);
        if (in_array('*', $permissions, true)) return;
        if (!in_array('admin_users', $permissions, true)) throw new AdminDelegationException();

        foreach (array_unique(array_merge(array_keys($before['roles']), array_keys($after['roles']))) as $role) {
            if (($before['roles'][$role] ?? null) === ($after['roles'][$role] ?? null)) continue;
            $this->assertSubset($before['roles'][$role] ?? [], $permissions);
            $this->assertSubset($after['roles'][$role] ?? [], $permissions);
        }
        foreach (array_unique(array_merge(array_keys($before['groups']), array_keys($after['groups']))) as $group) {
            if (($before['groups'][$group] ?? null) === ($after['groups'][$group] ?? null)) continue;
            foreach ([$before, $after] as $state) {
                $this->assertSubset($this->userPermissions(['groups' => [$group]], $state, true), $permissions);
            }
        }
        $oldUsers = array_column($before['users'], null, 'username');
        $newUsers = array_column($after['users'], null, 'username');
        foreach (array_unique(array_merge(array_keys($oldUsers), array_keys($newUsers))) as $name) {
            $old = $oldUsers[$name] ?? null;
            $new = $newUsers[$name] ?? null;
            $oldPermissions = $old === null ? [] : $this->userPermissions($old, $before);
            $newPermissions = $new === null ? [] : $this->userPermissions($new, $after);
            if ($old === $new && $oldPermissions === $newPermissions) continue;
            foreach ([$oldPermissions, $newPermissions] as $granted) $this->assertSubset($granted, $permissions);
            foreach ([$old, $new] as $target) {
                if ($target === null) continue;
                if (!(new FileNamePolicy())->containsPolicy($actor, $target)) throw new AdminDelegationException();
                $root = '/' . PathPolicy::normalizeRelative((string)($actor['home_dir'] ?? '/'));
                $home = '/' . PathPolicy::normalizeRelative((string)($target['home_dir'] ?? '/'));
                if (!PathPolicy::isWithinRoot($root, $home)) throw new AdminDelegationException();
            }
        }
    }

    /** UserModel already holds the same directory lock as the role snapshot. */
    public function assertUserChange(array $users, ?array $old, ?array $new, string $actor): void
    {
        $before = (new AuthorizationState())->snapshot();
        $before['users'] = $users;
        $after = $before;
        $name = $old['username'] ?? $new['username'];
        $after['users'] = array_values(array_filter($users, static fn(array $user): bool => $user['username'] !== $name));
        if ($new !== null) $after['users'][] = $new;
        $this->assertMutation($before, $after, $actor);
    }

    private function assertSubset(array $granted, array $actor): void
    {
        if (array_diff($granted, $actor) !== []) throw new AdminDelegationException();
    }

    private function userPermissions(array $user, array $state, bool $allowMissingGroup = false): array
    {
        if (isset($user['role']) && !isset($state['roles'][$user['role']])) throw new AdminDelegationException();
        foreach ($user['groups'] ?? [] as $group) {
            if (!isset($state['groups'][$group]) && !$allowMissingGroup) throw new AdminDelegationException();
        }
        return AuthorizationState::permissions($user, $state['roles'], $state['groups']);
    }
}
