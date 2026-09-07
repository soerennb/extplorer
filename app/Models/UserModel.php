<?php

namespace App\Models;

use App\Services\AtomicFileStore;
use App\Services\PasswordPolicy;
use Config\Services;
use InvalidArgumentException;

class UserModel
{
    private string $usersFile;
    private string $rolesFile;
    private string $groupsFile;

    public function __construct()
    {
        $storage = config('Storage');
        $this->usersFile = $storage->state . '/users.php';
        $this->rolesFile = $storage->state . '/roles.php';
        $this->groupsFile = $storage->state . '/groups.php';

    }

    private function loadData($path)
    {
        return AtomicFileStore::read($path);
    }

    private function saveData($path, $data)
    {
        AtomicFileStore::write($path, $data);
    }

    // --- Users ---

    public function isValidUsername(string $username): bool
    {
        $username = trim($username);
        // Keep usernames path-safe and predictable for storage/logging contexts.
        return (bool) preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{2,63}\z/', $username);
    }

    public function getUsers(): array
    {
        return $this->loadData($this->usersFile);
    }


    public function getUser(string $username): ?array
    {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if (($user['username'] ?? null) === $username) {
                return $this->withAuthDefaults($user);
            }
        }
        return null;
    }

    public function verifyUser(string $username, string $password): ?array
    {
        $user = $this->getUser($username);
        if (!$user || !is_string($user['password_hash'] ?? null)) {
            return null;
        }

        $now = time();
        if (!empty($user['disabled']) || (int)($user['locked_until'] ?? 0) > $now) {
            return null;
        }

        if (password_verify($password, $user['password_hash'])) {
            return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username, $password): ?array {
                foreach ($users as &$candidate) {
                    if (($candidate['username'] ?? null) !== $username) {
                        continue;
                    }

                    $candidate = $this->withAuthDefaults($candidate);
                    $candidate['failed_login_count'] = 0;
                    $candidate['locked_until'] = 0;
                    if (password_needs_rehash($candidate['password_hash'], PASSWORD_DEFAULT)) {
                        $candidate['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    return $candidate;
                }

                return null;
            }, []);
        }

        $this->recordFailedLogin($username);
        return null;
    }

    public function saveUsers(array $users): void
    {
        $this->saveData($this->usersFile, $users);
    }

    public function addUser(string $username, string $password, string $role = 'user', string $homeDir = '/', array $groups = [], string $allowedExt = '', string $blockedExt = '', bool $mustChangePassword = false): bool
    {
        $username = trim($username);
        if (!$this->isValidUsername($username)) {
            return false;
        }

        if (!array_key_exists($role, $this->getRoles())) {
            throw new InvalidArgumentException('Unknown user role.');
        }

        $passwordError = PasswordPolicy::validate($password);
        if ($passwordError !== null) {
            throw new InvalidArgumentException($passwordError);
        }

        return AtomicFileStore::transaction($this->usersFile, function (array &$users) use (
            $username,
            $password,
            $role,
            $homeDir,
            $groups,
            $allowedExt,
            $blockedExt,
            $mustChangePassword
        ): bool {
            foreach ($users as $user) {
                if (($user['username'] ?? null) === $username) {
                    return false;
                }
            }

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
                'recovery_codes' => [],
                'must_change_password' => $mustChangePassword,
                'auth_version' => 1,
                'disabled' => false,
                'failed_login_count' => 0,
                'locked_until' => 0,
            ];
            return true;
        });
    }

    public function updateUser(string $username, array $data): bool
    {
        if (array_key_exists('role', $data)) {
            if (!is_string($data['role']) || !array_key_exists($data['role'], $this->getRoles())) {
                throw new InvalidArgumentException('Unknown user role.');
            }
        }

        if (array_key_exists('password', $data)) {
            if (!is_string($data['password'])) {
                throw new InvalidArgumentException('Password must be a string.');
            }
            $passwordError = PasswordPolicy::validate($data['password']);
            if ($passwordError !== null) {
                throw new InvalidArgumentException($passwordError);
            }
        }

        return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username, $data): bool {
            foreach ($users as &$user) {
                if (($user['username'] ?? null) !== $username) {
                    continue;
                }

                $user = $this->withAuthDefaults($user);
                $currentlyActiveAdmin = ($user['role'] ?? '') === 'admin' && empty($user['disabled']);
                $nextRole = array_key_exists('role', $data) ? $data['role'] : $user['role'];
                $nextDisabled = array_key_exists('disabled', $data) ? (bool)$data['disabled'] : $user['disabled'];
                if ($currentlyActiveAdmin && ($nextRole !== 'admin' || $nextDisabled)) {
                    $activeAdministrators = 0;
                    foreach ($users as $candidate) {
                        if (($candidate['role'] ?? '') === 'admin' && empty($candidate['disabled'])) {
                            $activeAdministrators++;
                        }
                    }
                    if ($activeAdministrators <= 1) {
                        throw new \RuntimeException('The last active administrator cannot be disabled or demoted.');
                    }
                }

                $securityChanged = false;
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
                    $securityChanged = true;
                }
                
                if (array_key_exists('2fa_enabled', $data)) {
                    $user['2fa_enabled'] = (bool)$data['2fa_enabled'];
                    $securityChanged = true;
                }
                
                if (array_key_exists('recovery_codes', $data)) {
                    $val = $data['recovery_codes'];
                    if (!empty($val)) {
                        $enc = Services::encrypter();
                        $user['recovery_codes'] = base64_encode($enc->encrypt(json_encode($val)));
                    } else {
                        $user['recovery_codes'] = [];
                    }
                    $securityChanged = true;
                }

                if (array_key_exists('must_change_password', $data)) {
                    $user['must_change_password'] = (bool)$data['must_change_password'];
                }

                if (!empty($data['password'])) {
                    $user['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                    $securityChanged = true;
                }

                if (array_key_exists('disabled', $data)) {
                    $user['disabled'] = (bool)$data['disabled'];
                    $securityChanged = true;
                }
                if (array_key_exists('locked_until', $data)) {
                    $user['locked_until'] = max(0, (int)$data['locked_until']);
                    $securityChanged = true;
                }
                if (array_key_exists('failed_login_count', $data)) {
                    $user['failed_login_count'] = max(0, (int)$data['failed_login_count']);
                }

                if ($securityChanged) {
                    $user['auth_version']++;
                }

                return true;
            }

            return false;
        });
    }

    public function changePassword(string $username, string $newPassword): bool
    {
        return $this->updateUser($username, ['password' => $newPassword]);
    }

    /**
     * Adds explicit password-state metadata to users created by older releases.
     * The historical default is detected only during the one-time migration,
     * never as a branch in the authentication flow.
     */
    public function migratePasswordState(): int
    {
        return AtomicFileStore::transaction($this->usersFile, function (array &$users): int {
            $changed = 0;
            foreach ($users as &$user) {
                $before = $user;
                if (!array_key_exists('must_change_password', $user)) {
                    $legacyDefault = ($user['username'] ?? '') === 'admin'
                        && is_string($user['password_hash'] ?? null)
                        && password_verify('admin', $user['password_hash']);
                    $user['must_change_password'] = $legacyDefault;
                }
                $user = $this->withAuthDefaults($user);
                if ($user !== $before) {
                    $changed++;
                }
            }
            unset($user);
            return $changed;
        });
    }

    public function deleteUser(string $username): bool
    {
        return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username): bool {
            $target = null;
            $activeAdministrators = 0;
            foreach ($users as $user) {
                if (($user['username'] ?? null) === $username) {
                    $target = $user;
                }
                if (($user['role'] ?? '') === 'admin' && empty($user['disabled'])) {
                    $activeAdministrators++;
                }
            }

            if (is_array($target)
                && ($target['role'] ?? '') === 'admin'
                && empty($target['disabled'])
                && $activeAdministrators <= 1
            ) {
                throw new \RuntimeException('The last active administrator cannot be deleted.');
            }

            $newUsers = array_filter($users, fn($user) => ($user['username'] ?? null) !== $username);
            if (count($users) === count($newUsers)) return false;
            $users = array_values($newUsers);
            return true;
        });
    }

    public function bumpAuthVersion(string $username): bool
    {
        return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username): bool {
            foreach ($users as &$user) {
                if (($user['username'] ?? null) !== $username) {
                    continue;
                }
                $user = $this->withAuthDefaults($user);
                $user['auth_version']++;
                return true;
            }
            return false;
        });
    }

    /**
     * Atomically consumes one recovery code. The plaintext code is never
     * persisted and a simultaneous request can consume it only once.
     */
    public function consumeRecoveryCode(string $username, string $code): bool
    {
        return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username, $code): bool {
            foreach ($users as &$user) {
                if (($user['username'] ?? null) !== $username) {
                    continue;
                }

                $codes = $this->decodeRecoveryCodes($user['recovery_codes'] ?? []);
                $index = array_search($code, $codes, true);
                if ($index === false) {
                    return false;
                }

                unset($codes[$index]);
                $codes = array_values($codes);
                if ($codes === []) {
                    $user['recovery_codes'] = [];
                } else {
                    $enc = Services::encrypter();
                    $user['recovery_codes'] = base64_encode($enc->encrypt(json_encode($codes, JSON_THROW_ON_ERROR)));
                }
                $user = $this->withAuthDefaults($user);
                $user['auth_version']++;
                return true;
            }
            return false;
        });
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

        return $this->decodeRecoveryCodes($user['recovery_codes']);
    }

    private function withAuthDefaults(array $user): array
    {
        $user['must_change_password'] = (bool)($user['must_change_password'] ?? false);
        $user['auth_version'] = max(1, (int)($user['auth_version'] ?? 1));
        $user['disabled'] = (bool)($user['disabled'] ?? false);
        $user['failed_login_count'] = max(0, (int)($user['failed_login_count'] ?? 0));
        $user['locked_until'] = max(0, (int)($user['locked_until'] ?? 0));
        return $user;
    }

    private function recordFailedLogin(string $username): void
    {
        AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username): void {
            foreach ($users as &$user) {
                if (($user['username'] ?? null) !== $username) {
                    continue;
                }

                $user = $this->withAuthDefaults($user);
                $user['failed_login_count']++;
                if ($user['failed_login_count'] >= 5) {
                    $user['locked_until'] = time() + 900;
                    \App\Services\LogService::log(
                        'User Locked',
                        '',
                        'Account temporarily locked after repeated failed logins.',
                        $username
                    );
                }
                return;
            }
        });
    }

    private function decodeRecoveryCodes(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, 'is_string'));
        }
        if (!is_string($value) || $value === '') {
            return [];
        }

        try {
            $enc = Services::encrypter();
            $json = $enc->decrypt(base64_decode($value, true));
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // --- Roles & Permissions ---

    public function getRoles(): array
    {
        $roles = $this->loadData($this->rolesFile);
        if (empty($roles)) {
            $roles = [
                'admin' => ['*', 'admin_settings'],
                'user'  => ['read', 'write', 'upload', 'delete', 'rename', 'archive', 'extract', 'chmod']
            ];
        }
        return $roles;
    }

    public function saveRoles(array $roles): void
    {
        $this->saveData($this->rolesFile, $roles);
    }

    /**
     * Returns dependency information for a role.
     * Used to guard against deleting roles that are still in use.
     */
    public function getRoleUsage(string $roleName): array
    {
        $users = $this->getUsers();
        $groups = $this->getGroups();

        $directUsers = [];
        foreach ($users as $user) {
            if (($user['role'] ?? null) === $roleName) {
                $directUsers[] = $user['username'];
            }
        }

        $groupsUsingRole = [];
        foreach ($groups as $groupName => $roleNames) {
            if (is_array($roleNames) && in_array($roleName, $roleNames, true)) {
                $groupsUsingRole[] = $groupName;
            }
        }

        $usersViaGroups = [];
        if (!empty($groupsUsingRole)) {
            foreach ($users as $user) {
                $userGroups = $user['groups'] ?? [];
                if (!is_array($userGroups) || empty($userGroups)) {
                    continue;
                }
                if (array_intersect($userGroups, $groupsUsingRole)) {
                    $usersViaGroups[] = $user['username'];
                }
            }
        }

        return [
            'role' => $roleName,
            'direct_users' => $directUsers,
            'direct_users_count' => count($directUsers),
            'groups' => $groupsUsingRole,
            'groups_count' => count($groupsUsingRole),
            'users_via_groups' => array_values(array_unique($usersViaGroups)),
            'users_via_groups_count' => count(array_unique($usersViaGroups)),
        ];
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

    /**
     * Returns dependency information for a group.
     * Used to guard against deleting groups that are still assigned to users.
     */
    public function getGroupUsage(string $groupName): array
    {
        $users = $this->getUsers();
        $groups = $this->getGroups();

        $assignedUsers = [];
        foreach ($users as $user) {
            $userGroups = $user['groups'] ?? [];
            if (!is_array($userGroups)) {
                continue;
            }
            if (in_array($groupName, $userGroups, true)) {
                $assignedUsers[] = $user['username'];
            }
        }

        $rolesGranted = $groups[$groupName] ?? [];
        if (!is_array($rolesGranted)) {
            $rolesGranted = [];
        }

        return [
            'group' => $groupName,
            'assigned_users' => $assignedUsers,
            'assigned_users_count' => count($assignedUsers),
            'roles' => $rolesGranted,
            'roles_count' => count($rolesGranted),
        ];
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
