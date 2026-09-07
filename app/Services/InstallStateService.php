<?php

namespace App\Services;

use App\Models\UserModel;
use RuntimeException;

/**
 * Owns the state transition from an uninitialized installation to an
 * installed one.  The web installer and the CLI bootstrap must use the same
 * rules so that an HTTP request can never claim a partially initialized
 * installation.
 */
final class InstallStateService
{
    public const STATUS_CLAIMABLE = 'claimable';
    public const STATUS_INSTALLED = 'installed';
    public const STATUS_REPAIR = 'repair';
    public const CLAIM_TOKEN_TTL = 86400;

    private string $root;
    private string $tokenPath;
    private string $markerPath;
    private string $lockPath;

    public function __construct()
    {
        $storage = config('Storage');
        $this->root = rtrim($storage->root, '/\\');
        $this->tokenPath = $this->root . '/.extplorer-install-token';
        $this->markerPath = $this->root . '/installed.lock';
        $this->lockPath = $this->root . '/.install.lock';
    }

    public function status(): string
    {
        $users = (new UserModel())->getUsers();
        $hasUsers = $users !== [];
        $hasAdmin = $hasUsers && $this->hasAdministrator($users);
        $hasMarker = is_file($this->markerPath);

        if (!$hasUsers && !$hasMarker) {
            return self::STATUS_CLAIMABLE;
        }

        if ($hasUsers && $hasAdmin && $hasMarker) {
            return self::STATUS_INSTALLED;
        }

        return self::STATUS_REPAIR;
    }

    public function tokenPath(): string
    {
        return $this->tokenPath;
    }

    public function ensureClaimToken(): void
    {
        $this->withLock(function (): void {
            if ($this->status() !== self::STATUS_CLAIMABLE) {
                throw new RuntimeException('Installation is not available for a new claim.');
            }

            $this->ensureRoot();
            if (is_file($this->tokenPath) && $this->isFreshToken()) {
                return;
            }

            $token = bin2hex(random_bytes(32));
            $temporary = tempnam($this->root, '.extplorer-install-token-');
            if ($temporary === false) {
                throw new RuntimeException('Unable to create the installation claim token.');
            }

            try {
                if (file_put_contents($temporary, $token . PHP_EOL, LOCK_EX) === false) {
                    throw new RuntimeException('Unable to write the installation claim token.');
                }
                if (!chmod($temporary, 0600) || !rename($temporary, $this->tokenPath)) {
                    throw new RuntimeException('Unable to activate the installation claim token.');
                }
            } finally {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        });
    }

    /**
     * @return array{username: string}
     */
    public function claim(string $token, string $username, string $password): array
    {
        return $this->withLock(function () use ($token, $username, $password): array {
            if ($this->status() !== self::STATUS_CLAIMABLE) {
                throw new RuntimeException('Installation is not available for a new claim.');
            }

            $expected = $this->readClaimToken();
            if ($expected === '' || !hash_equals($expected, trim($token))) {
                throw new RuntimeException('Invalid installation claim token.');
            }

            $model = new UserModel();
            if (!$model->isValidUsername($username)) {
                throw new RuntimeException('Invalid administrator username.');
            }

            $passwordError = PasswordPolicy::validate($password);
            if ($passwordError !== null) {
                throw new RuntimeException($passwordError);
            }

            $this->createInitialAdmin($model, $username, $password);
            $this->consumeClaimToken();
            $this->writeInstallationMarker();

            return ['username' => $username];
        });
    }

    /**
     * Bootstrap the administrator from the configured secret for CLI and
     * container initialization.  This is intentionally implemented here so
     * the web installer and non-HTTP installations cannot drift apart.
     */
    public function bootstrapFromEnvironment(): string
    {
        return $this->withLock(function (): string {
            $model = new UserModel();
            $status = $this->status();
            $statePath = config('Storage')->state . '/admin-bootstrap.php';
            $state = AtomicFileStore::read($statePath, []);
            $users = $model->getUsers();
            $admins = array_values(array_filter(
                $users,
                static fn(array $user): bool => ($user['role'] ?? '') === 'admin' && empty($user['disabled'])
            ));

            if ($status === self::STATUS_CLAIMABLE) {
                $username = $this->adminUsername();
                $password = SecretReader::environment(
                    'EXTPLORER_ADMIN_PASSWORD_FILE',
                    'EXTPLORER_ADMIN_PASS'
                );
                $passwordError = PasswordPolicy::validate($password);
                if ($passwordError !== null) {
                    throw new RuntimeException($passwordError);
                }

                $this->createInitialAdmin($model, $username, $password);
                $this->consumeClaimToken();
                $this->writeInstallationMarker();
                $state['initialized_at'] = gmdate(DATE_ATOM);
                $state['username'] = $username;
            } elseif ($status === self::STATUS_REPAIR && $admins === []) {
                throw new RuntimeException(
                    'Persistent user data exists but no administrator account was found. '
                    . 'Create an administrator explicitly with the CLI before starting the service.'
                );
            } elseif ($status === self::STATUS_REPAIR) {
                $this->writeInstallationMarker();
                $state['repaired_at'] = gmdate(DATE_ATOM);
            }

            if ($this->resetRequested()) {
                $username = $this->adminUsername();
                $password = SecretReader::environment(
                    'EXTPLORER_ADMIN_PASSWORD_FILE',
                    'EXTPLORER_ADMIN_PASS'
                );
                $passwordError = PasswordPolicy::validate($password);
                if ($passwordError !== null) {
                    throw new RuntimeException($passwordError);
                }
                $target = $model->getUser($username);
                if ($target === null || ($target['role'] ?? '') !== 'admin') {
                    throw new RuntimeException("Administrator account not found for reset: {$username}");
                }

                $fingerprint = hash('sha256', $username . "\0" . $password);
                $previous = $state['last_reset'] ?? [];
                if (($previous['username'] ?? '') !== $username || ($previous['fingerprint'] ?? '') !== $fingerprint) {
                    if (!$model->changePassword($username, $password)) {
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

            return 'Administrator bootstrap completed.';
        });
    }

    private function readClaimToken(): string
    {
        if (!is_file($this->tokenPath) || !$this->isFreshToken()) {
            throw new RuntimeException('Installation claim token is missing or expired.');
        }

        $token = file_get_contents($this->tokenPath);
        if ($token === false) {
            throw new RuntimeException('Unable to read the installation claim token.');
        }

        return trim($token);
    }

    private function isFreshToken(): bool
    {
        $mtime = filemtime($this->tokenPath);
        return $mtime !== false && $mtime >= (time() - self::CLAIM_TOKEN_TTL);
    }

    private function createInitialAdmin(UserModel $model, string $username, string $password): void
    {
        $roles = $model->getRoles();
        $roles['admin'] = ['*', 'admin_settings'];
        $roles['user'] = ['read', 'write', 'upload', 'delete', 'rename', 'archive', 'extract', 'chmod'];
        $model->saveRoles($roles);

        $groups = $model->getGroups();
        $groups['Administrators'] = ['admin'];
        $model->saveGroups($groups);

        if (!$model->addUser($username, $password, 'admin', '/', ['Administrators'], '', '', true)) {
            throw new RuntimeException('Unable to create the initial administrator account.');
        }
    }

    private function consumeClaimToken(): void
    {
        if (is_file($this->tokenPath) && !unlink($this->tokenPath) && is_file($this->tokenPath)) {
            throw new RuntimeException('Unable to consume the installation claim token.');
        }
    }

    private function writeInstallationMarker(): void
    {
        $marker = $this->markerPath . '.tmp-' . bin2hex(random_bytes(8));
        try {
            if (file_put_contents($marker, gmdate(DATE_ATOM) . PHP_EOL, LOCK_EX) === false
                || !chmod($marker, 0600)
                || !rename($marker, $this->markerPath)) {
                throw new RuntimeException('Unable to write the installation completion marker.');
            }
        } finally {
            if (is_file($marker)) {
                unlink($marker);
            }
        }
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

    private function hasAdministrator(array $users): bool
    {
        foreach ($users as $user) {
            if (($user['role'] ?? '') === 'admin' && empty($user['disabled'])) {
                return true;
            }
        }

        return false;
    }

    private function ensureRoot(): void
    {
        if (!is_dir($this->root) && !mkdir($this->root, 0750, true) && !is_dir($this->root)) {
            throw new RuntimeException('Unable to create the writable directory.');
        }
        if (!is_writable($this->root)) {
            throw new RuntimeException('The writable directory is not writable.');
        }
    }

    private function withLock(callable $callback): mixed
    {
        $this->ensureRoot();
        $lock = fopen($this->lockPath, 'c');
        if ($lock === false) {
            throw new RuntimeException('Unable to open the installation lock.');
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to acquire the installation lock.');
            }
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
