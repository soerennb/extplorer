<?php

namespace App\Services;

use RuntimeException;

/** Users, roles and groups form one authorization snapshot and mutation boundary. */
final class AuthorizationState
{
    public const DEFAULT_ROLES = [
        'admin' => ['*', 'admin_settings'],
        'user' => ['read', 'write', 'upload', 'delete', 'rename', 'archive', 'extract', 'chmod'],
    ];

    private array $paths;

    public function __construct(?string $directory = null)
    {
        $directory ??= config('Storage')->state;
        $this->paths = [
            'users' => $directory . '/users.php',
            'roles' => $directory . '/roles.php',
            'groups' => $directory . '/groups.php',
        ];
    }

    public function snapshot(): array
    {
        return $this->decode(AtomicFileStore::readMany($this->defaults()));
    }

    public function replaceRoles(array $roles): void
    {
        $this->mutate(function (array &$state) use ($roles): void { $state['roles'] = $roles; });
    }

    public function replaceGroups(array $groups): void
    {
        $this->mutate(function (array &$state) use ($groups): void { $state['groups'] = $groups; });
    }

    public function saveRole(string $name, array $permissions): void
    {
        $this->mutate(function (array &$state) use ($name, $permissions): void { $state['roles'][$name] = $permissions; });
    }

    public function deleteRole(string $name): void
    {
        $this->mutate(function (array &$state) use ($name): void {
            if (in_array($name, ['admin', 'user'], true)) throw new RuntimeException('This system role cannot be deleted.');
            foreach ($state['users'] as $user) {
                if (($user['role'] ?? '') === $name) throw new RuntimeException('Role is still assigned to users or groups.');
            }
            foreach ($state['groups'] as $roles) {
                if (in_array($name, $roles, true)) throw new RuntimeException('Role is still assigned to users or groups.');
            }
            unset($state['roles'][$name]);
        });
    }

    public function saveGroup(string $name, array $roles): void
    {
        $this->mutate(function (array &$state) use ($name, $roles): void {
            foreach ($roles as $role) if (!isset($state['roles'][$role])) throw new RuntimeException('Group contains an unknown role.');
            $state['groups'][$name] = $roles;
        });
    }

    public function deleteGroup(string $name): void
    {
        $this->mutate(function (array &$state) use ($name): void {
            foreach ($state['users'] as $user) {
                if (in_array($name, $user['groups'] ?? [], true)) throw new RuntimeException('Group is still assigned to users.');
            }
            unset($state['groups'][$name]);
        });
    }

    public static function permissions(array $user, array $roles, array $groups): array
    {
        $assigned = isset($user['role']) ? [$user['role']] : [];
        foreach ($user['groups'] ?? [] as $group) $assigned = array_merge($assigned, $groups[$group] ?? []);
        $permissions = [];
        foreach (array_unique($assigned) as $role) $permissions = array_merge($permissions, $roles[$role] ?? []);
        return in_array('*', $permissions, true) ? ['*'] : array_values(array_unique($permissions));
    }

    private function mutate(callable $callback): void
    {
        AtomicFileStore::transactionMany($this->defaults(), function (array &$documents) use ($callback): void {
            $state = $before = $this->decode($documents);
            $callback($state);
            $changedRoles = $this->changedKeys($before['roles'], $state['roles']);
            $changedGroups = $this->changedKeys($before['groups'], $state['groups']);
            foreach ($state['users'] as &$user) {
                $groups = is_array($user['groups'] ?? null) ? $user['groups'] : [];
                $assignedRoles = [$user['role'] ?? ''];
                foreach ($groups as $group) $assignedRoles = array_merge($assignedRoles, $before['groups'][$group] ?? []);
                if (array_intersect($changedRoles, $assignedRoles) || array_intersect($changedGroups, $groups)) {
                    $user['auth_version'] = max(1, (int)($user['auth_version'] ?? 1)) + 1;
                }
            }
            unset($user);
            foreach ($this->paths as $name => $path) $documents[$path] = $state[$name];
        });
    }

    private function changedKeys(array $before, array $after): array
    {
        return array_values(array_filter(array_unique(array_merge(array_keys($before), array_keys($after))),
            static fn($name) => ($before[$name] ?? null) !== ($after[$name] ?? null)));
    }

    private function defaults(): array
    {
        return [$this->paths['users'] => [], $this->paths['roles'] => self::DEFAULT_ROLES, $this->paths['groups'] => []];
    }

    private function decode(array $documents): array
    {
        return [
            'users' => $documents[$this->paths['users']],
            'roles' => $documents[$this->paths['roles']] ?: self::DEFAULT_ROLES,
            'groups' => $documents[$this->paths['groups']],
        ];
    }
}
