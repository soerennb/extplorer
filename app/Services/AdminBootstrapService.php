<?php

namespace App\Services;

use App\Models\UserModel;
use RuntimeException;

final class AdminBootstrapService
{
    public function bootstrapFromEnvironment(): string
    {
        $storage = config('Storage');
        $statePath = $storage->state . '/admin-bootstrap.php';
        $state = AtomicFileStore::read($statePath, []);
        $userModel = new UserModel();
        $users = $userModel->getUsers();
        $admins = array_values(array_filter(
            $users,
            static fn(array $user): bool => ($user['role'] ?? '') === 'admin'
        ));

        if ($users === []) {
            $username = $this->adminUsername();
            $password = SecretReader::environment(
                'EXTPLORER_ADMIN_PASSWORD_FILE',
                'EXTPLORER_ADMIN_PASS'
            );

            $roles = $userModel->getRoles();
            if (!isset($roles['admin'])) {
                $roles['admin'] = ['*', 'admin_settings'];
                $userModel->saveRoles($roles);
            }
            $groups = $userModel->getGroups();
            if (!isset($groups['Administrators'])) {
                $groups['Administrators'] = ['admin'];
                $userModel->saveGroups($groups);
            }

            if (!$userModel->addUser($username, $password, 'admin', '/', ['Administrators'])) {
                throw new RuntimeException('Unable to create the initial administrator account.');
            }
            $admins = [$userModel->getUser($username)];
            $state['initialized_at'] = gmdate(DATE_ATOM);
            $state['username'] = $username;
        } elseif ($admins === []) {
            throw new RuntimeException(
                'Persistent user data exists but no administrator account was found. '
                . 'Create an administrator explicitly with the CLI before starting the service.'
            );
        }

        if ($this->resetRequested()) {
            $username = $this->adminUsername();
            $password = SecretReader::environment(
                'EXTPLORER_ADMIN_PASSWORD_FILE',
                'EXTPLORER_ADMIN_PASS'
            );
            $target = $userModel->getUser($username);
            if ($target === null || ($target['role'] ?? '') !== 'admin') {
                throw new RuntimeException("Administrator account not found for reset: {$username}");
            }

            $fingerprint = hash('sha256', $username . "\0" . $password);
            $previous = $state['last_reset'] ?? [];
            if (($previous['username'] ?? '') !== $username || ($previous['fingerprint'] ?? '') !== $fingerprint) {
                if (!$userModel->changePassword($username, $password)) {
                    throw new RuntimeException("Unable to reset administrator password: {$username}");
                }
                $state['last_reset'] = [
                    'username' => $username,
                    'fingerprint' => $fingerprint,
                    'completed_at' => gmdate(DATE_ATOM),
                ];
            }
        }

        AtomicFileStore::write($statePath, array_merge($state, [
            'status' => 'initialized',
            'updated_at' => gmdate(DATE_ATOM),
        ]));

        $lockPath = $storage->root . '/installed.lock';
        if (!is_file($lockPath) && file_put_contents($lockPath, gmdate(DATE_ATOM) . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the installation lock.');
        }

        return 'Administrator bootstrap completed.';
    }

    private function adminUsername(): string
    {
        $username = getenv('EXTPLORER_ADMIN_USER');
        $username = ($username === false || trim($username) === '') ? 'admin' : trim($username);
        $model = new UserModel();
        if (!$model->isValidUsername($username)) {
            throw new RuntimeException('EXTPLORER_ADMIN_USER has an invalid format.');
        }
        return $username;
    }

    private function resetRequested(): bool
    {
        $value = getenv('EXTPLORER_ADMIN_RESET_PASSWORD');
        return $value !== false && in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }
}
