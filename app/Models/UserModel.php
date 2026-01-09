<?php

namespace App\Models;

class UserModel
{
    private string $usersFile;
    private string $rolesFile;
    private string $groupsFile;

    public function __construct()
    {
        $this->usersFile = WRITEPATH . 'users.json';
        $this->rolesFile = WRITEPATH . 'roles.json';
        $this->groupsFile = WRITEPATH . 'groups.json';

        if (!file_exists($this->usersFile)) file_put_contents($this->usersFile, json_encode([]));
        if (!file_exists($this->rolesFile)) file_put_contents($this->rolesFile, json_encode([]));
        if (!file_exists($this->groupsFile)) file_put_contents($this->groupsFile, json_encode([]));
    }

    // --- Users ---

    public function getUsers(): array
    {
        return json_decode(file_get_contents($this->usersFile), true) ?? [];
    }

    public function getUser(string $username): ?array
    {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }

    public function verifyUser(string $username, string $password): ?array
    {
        $user = $this->getUser($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public function saveUsers(array $users): void
    {
        file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }

    public function addUser(string $username, string $password, string $role = 'user', string $homeDir = '/', array $groups = [], string $allowedExt = '', string $blockedExt = ''): bool
    {
        if ($this->getUser($username)) {
            return false;
        }
        $users = $this->getUsers();
        $users[] = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'home_dir' => $homeDir,
            'groups' => $groups,
            'allowed_extensions' => $allowedExt,
            'blocked_extensions' => $blockedExt
        ];
        $this->saveUsers($users);
        return true;
    }

    public function updateUser(string $username, array $data): bool
    {
        $users = $this->getUsers();
        foreach ($users as &$user) {
            if ($user['username'] === $username) {
                if (isset($data['role'])) $user['role'] = $data['role'];
                if (isset($data['home_dir'])) $user['home_dir'] = $data['home_dir'];
                if (isset($data['groups'])) $user['groups'] = $data['groups'];
                if (isset($data['allowed_extensions'])) $user['allowed_extensions'] = $data['allowed_extensions'];
                if (isset($data['blocked_extensions'])) $user['blocked_extensions'] = $data['blocked_extensions'];
                if (!empty($data['password'])) {
                    $user['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                $this->saveUsers($users);
                return true;
            }
        }
        return false;
    }

    public function changePassword(string $username, string $newPassword): bool
    {
        return $this->updateUser($username, ['password' => $newPassword]);
    }

    public function deleteUser(string $username): bool
    {
        $users = $this->getUsers();
        $newUsers = array_filter($users, fn($u) => $u['username'] !== $username);
        if (count($users) === count($newUsers)) return false;
        $this->saveUsers(array_values($newUsers));
        return true;
    }

    // --- Roles & Permissions ---

    public function getRoles(): array
    {
        return json_decode(file_get_contents($this->rolesFile), true) ?? [];
    }

    public function saveRoles(array $roles): void
    {
        file_put_contents($this->rolesFile, json_encode($roles, JSON_PRETTY_PRINT));
    }

    // --- Groups ---

    public function getGroups(): array
    {
        return json_decode(file_get_contents($this->groupsFile), true) ?? [];
    }

    public function saveGroups(array $groups): void
    {
        file_put_contents($this->groupsFile, json_encode($groups, JSON_PRETTY_PRINT));
    }

    // --- Resolution ---

    public function getPermissions(string $username): array
    {
        $user = $this->getUser($username);
        if (!$user) return [];

        $allRoles = $this->getRoles();
        $allGroups = $this->getGroups();

        $userRoles = [];
        // Direct role
        if (!empty($user['role'])) {
            $userRoles[] = $user['role'];
        }
        // Group roles
        if (!empty($user['groups']) && is_array($user['groups'])) {
            foreach ($user['groups'] as $groupName) {
                if (isset($allGroups[$groupName])) {
                    $userRoles = array_merge($userRoles, $allGroups[$groupName]);
                }
            }
        }

        $permissions = [];
        foreach ($userRoles as $role) {
            if (isset($allRoles[$role])) {
                $permissions = array_merge($permissions, $allRoles[$role]);
            }
        }

        if (in_array('*', $permissions)) return ['*'];

        return array_values(array_unique($permissions));
    }
}
