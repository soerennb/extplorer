<?php

namespace App\Models;

use Config\Services;

class UserModel
{
    private string $usersFile;
    private string $rolesFile;
    private string $groupsFile;

    public function __construct()
    {
        $this->usersFile = WRITEPATH . 'users.php';
        $this->rolesFile = WRITEPATH . 'roles.php';
        $this->groupsFile = WRITEPATH . 'groups.php';

        $this->migrateFile(WRITEPATH . 'users.json', $this->usersFile);
        $this->migrateFile(WRITEPATH . 'roles.json', $this->rolesFile);
        $this->migrateFile(WRITEPATH . 'groups.json', $this->groupsFile);

        if (!file_exists($this->usersFile)) $this->saveData($this->usersFile, []);
        if (!file_exists($this->rolesFile)) $this->saveData($this->rolesFile, []);
        if (!file_exists($this->groupsFile)) $this->saveData($this->groupsFile, []);
    }

    private function migrateFile($oldPath, $newPath)
    {
        if (file_exists($oldPath) && !file_exists($newPath)) {
            $data = json_decode(file_get_contents($oldPath), true) ?? [];
            $this->saveData($newPath, $data);
            unlink($oldPath);
        }
    }

    private function loadData($path)
    {
        if (!file_exists($path)) return [];
        $content = file_get_contents($path);
        if (strpos($content, '<?php') === 0) {
            // Remove the first line safely
            $content = substr($content, strpos($content, "\n") + 1);
        }
        return json_decode($content, true) ?? [];
    }

    private function saveData($path, $data)
    {
        $content = '<?php die("Access denied"); ?>' . PHP_EOL . json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($path, $content);
    }

    // --- Users ---

    public function getUsers(): array
    {
        return $this->loadData($this->usersFile);
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
        $this->saveData($this->usersFile, $users);
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
            'blocked_extensions' => $blockedExt,
            '2fa_secret' => null,
            '2fa_enabled' => false,
            'recovery_codes' => []
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

                // 2FA Fields
                if (array_key_exists('2fa_secret', $data)) {
                    $val = $data['2fa_secret'];
                    if ($val) {
                        $enc = Services::encrypter();
                        $user['2fa_secret'] = base64_encode($enc->encrypt($val));
                    } else {
                        $user['2fa_secret'] = null;
                    }
                }
                
                if (array_key_exists('2fa_enabled', $data)) $user['2fa_enabled'] = $data['2fa_enabled'];
                
                if (array_key_exists('recovery_codes', $data)) {
                    $val = $data['recovery_codes'];
                    if (!empty($val)) {
                        $enc = Services::encrypter();
                        $user['recovery_codes'] = base64_encode($enc->encrypt(json_encode($val)));
                    } else {
                        $user['recovery_codes'] = [];
                    }
                }

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

    public function get2faSecret(string $username): ?string
    {
        $user = $this->getUser($username);
        if (!$user || empty($user['2fa_secret'])) return null;
        
        try {
            $enc = Services::encrypter();
            return $enc->decrypt(base64_decode($user['2fa_secret']));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getRecoveryCodes(string $username): array
    {
        $user = $this->getUser($username);
        if (!$user || empty($user['recovery_codes'])) return [];
        
        try {
            // Check if it's already an array (unencrypted legacy)
            if (is_array($user['recovery_codes'])) return $user['recovery_codes'];

            $enc = Services::encrypter();
            $json = $enc->decrypt(base64_decode($user['recovery_codes']));
            return json_decode($json, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    // --- Roles & Permissions ---

    public function getRoles(): array
    {
        $roles = $this->loadData($this->rolesFile);
        if (empty($roles)) {
            $roles = [
                'admin' => ['*'],
                'user'  => ['read', 'write', 'upload', 'delete', 'rename', 'archive', 'extract', 'chmod']
            ];
            $this->saveRoles($roles);
        }
        return $roles;
    }

    public function saveRoles(array $roles): void
    {
        $this->saveData($this->rolesFile, $roles);
    }

    // --- Groups ---

    public function getGroups(): array
    {
        return $this->loadData($this->groupsFile);
    }

    public function saveGroups(array $groups): void
    {
        $this->saveData($this->groupsFile, $groups);
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
