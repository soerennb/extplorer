<?php

namespace App\Models;

use App\Services\AtomicFileStore;
use App\Services\AuthorizationState;
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

    /**
     * The account-wide lock against password guessing does not apply to a
     * trusted device, whose failures are limited separately and not counted
     * against the account (see DeviceTrustService).
     */
    public function verifyUser(string $username, string $password, bool $trustedDevice = false): ?array
    {
        $user = $this->getUser($username);
        if (!$user || !is_string($user['password_hash'] ?? null)) {
            self::equalizeTiming($password);
            return null;
        }

        $now = time();
        if (!empty($user['disabled']) || (!$trustedDevice && (int)($user['locked_until'] ?? 0) > $now)) {
            self::equalizeTiming($password);
            return null;
        }

        if (password_verify($password, $user['password_hash'])) {
            return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username, $password, $user, $trustedDevice): ?array {
                foreach ($users as &$candidate) {
                    if (($candidate['username'] ?? null) !== $username) {
                        continue;
                    }

                    $candidate = $this->withAuthDefaults($candidate);
                    if ($candidate['disabled'] || (!$trustedDevice && $candidate['locked_until'] > time())
                        || $candidate['password_hash'] !== $user['password_hash']
                        || $candidate['auth_version'] !== $user['auth_version']) return null;
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

        if (!$trustedDevice) {
            $this->recordFailedLogin($username);
        }
        return null;
    }

    /**
     * Rejections without a hash comparison would reveal unknown, disabled
     * or locked accounts by their response time. The dummy hash uses this
     * platform's default cost, like stored hashes.
     */
    private static function equalizeTiming(string $password): void
    {
        static $dummyHash = null;
        $dummyHash ??= password_hash('extplorer-timing-equalizer', PASSWORD_DEFAULT);
        password_verify($password, $dummyHash);
    }

    public function saveUsers(array $users): void
    {
        $this->saveData($this->usersFile, $users);
    }

    public function addUser(string $username, string $password, string $role = 'user', string $homeDir = '/', array $groups = [], string $allowedExt = '', string $blockedExt = '', bool $mustChangePassword = false, ?string $actor = null): bool
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
            $mustChangePassword,
            $actor
        ): bool {
            foreach ($users as $user) {
                if (($user['username'] ?? null) === $username) {
                    return false;
                }
            }

            $newUser = [
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
            if ($actor !== null) (new \App\Services\AdminDelegationPolicy())->assertUserChange($users, null, $newUser, $actor);
            $users[] = $newUser;
            return true;
        });
    }

    public function updateUser(string $username, array $data, ?string $actor = null): bool
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

        return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username, $data, $actor): bool {
            foreach ($users as &$user) {
                if (($user['username'] ?? null) !== $username) {
                    continue;
                }

                if ($actor !== null) {
                    (new \App\Services\AdminDelegationPolicy())->assertUserChange($users, $user, array_replace($user, $data), $actor);
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
                foreach (['role', 'home_dir', 'groups', 'allowed_extensions', 'blocked_extensions'] as $field) {
                    if (array_key_exists($field, $data) && ($user[$field] ?? null) !== $data[$field]) {
                        $user[$field] = $data[$field];
                        $securityChanged = true;
                    }
                }

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

    /**
     * Clears the password-change flag left by the pre-setup-flow release,
     * but only when the account is unambiguously the original administrator.
     *
     * The literal historical default password remains protected by the flag.
     * This migration is intentionally separate from authentication so that a
     * normal login can never infer state from a password value.
     */
    public function migrateLegacyInitialAdminPasswordState(): int
    {
        return AtomicFileStore::transaction($this->usersFile, function (array &$users): int {
            if ($users === []) {
                return 0;
            }

            $activeAdministrators = 0;
            foreach ($users as $user) {
                if (is_array($user)
                    && ($user['role'] ?? '') === 'admin'
                    && empty($user['disabled'])
                ) {
                    $activeAdministrators++;
                }
            }

            if ($activeAdministrators !== 1) {
                return 0;
            }

            $firstKey = array_key_first($users);
            if (!is_array($users[$firstKey])) {
                return 0;
            }

            $candidate =& $users[$firstKey];
            $groups = $candidate['groups'] ?? null;
            $passwordHash = $candidate['password_hash'] ?? null;
            $hashInfo = is_string($passwordHash) ? password_get_info($passwordHash) : [];

            if (($candidate['role'] ?? '') !== 'admin'
                || !empty($candidate['disabled'])
                || !is_array($groups)
                || !in_array('Administrators', $groups, true)
                || ($candidate['must_change_password'] ?? false) !== true
                || !array_key_exists('auth_version', $candidate)
                || (int)$candidate['auth_version'] !== 1
                || !is_string($passwordHash)
                || (int)($hashInfo['algo'] ?? 0) === 0
                || password_verify('admin', $passwordHash)
            ) {
                return 0;
            }

            $candidate['must_change_password'] = false;
            $candidate['auth_version']++;
            return 1;
        });
    }

    public function deleteUser(string $username, ?string $actor = null): bool
    {
        return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username, $actor): bool {
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

            if ($actor !== null && $target !== null) {
                (new \App\Services\AdminDelegationPolicy())->assertUserChange($users, $target, null, $actor);
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
    public function consumeRecoveryCode(string $username, string $code, ?int $expectedVersion = null): bool
    {
        return AtomicFileStore::transaction($this->usersFile, function (array &$users) use ($username, $code, $expectedVersion): bool {
            foreach ($users as &$user) {
                if (($user['username'] ?? null) !== $username) {
                    continue;
                }

                if ($expectedVersion !== null && (int)($user['auth_version'] ?? 1) !== $expectedVersion) return false;
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
                if ($user['locked_until'] > 0 && $user['locked_until'] <= time()) {
                    // An expired lock starts a new failure window; otherwise
                    // a single failure per lock period would re-lock forever.
                    $user['failed_login_count'] = 0;
                    $user['locked_until'] = 0;
                }
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
        (new AuthorizationState())->replaceRoles($roles);
    }

    public function saveRole(string $name, array $permissions, ?string $actor = null): void
    {
        (new AuthorizationState())->saveRole($name, $permissions, $actor);
    }

    public function deleteRole(string $name, ?string $actor = null): void
    {
        (new AuthorizationState())->deleteRole($name, $actor);
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
        (new AuthorizationState())->replaceGroups($groups);
    }

    public function saveGroup(string $name, array $roles, ?string $actor = null): void
    {
        (new AuthorizationState())->saveGroup($name, $roles, $actor);
    }

    public function deleteGroup(string $name, ?string $actor = null): void
    {
        (new AuthorizationState())->deleteGroup($name, $actor);
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
        $snapshot = $this->authorizationSnapshot($username);
        return $snapshot['permissions'] ?? [];
    }

    /** @return array{user: array, permissions: array}|null */
    public function authorizationSnapshot(string $username): ?array
    {
        $state = (new AuthorizationState())->snapshot();
        foreach ($state['users'] as $user) {
            if (($user['username'] ?? '') === $username) {
                $user = $this->withAuthDefaults($user);
                return ['user' => $user, 'permissions' => AuthorizationState::permissions($user, $state['roles'], $state['groups'])];
            }
        }
        return null;
    }
}
