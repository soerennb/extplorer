<?php

namespace App\Services\VFS;

use App\Models\UserModel;

class VfsFactory
{
    private static function revealConnectionSecret(string $secret): string
    {
        return (new \App\Services\RemoteCredentialService())->reveal($secret);
    }

    public static function createFileSystem(?string $username = null, array $connection = []): IFileSystem
    {
        if ($username === null || trim($username) === '') {
            return new DeniedFileSystem();
        }

        $mode = $connection['mode'] ?? 'local';

        if ($mode !== 'local') return self::createAdapter($mode, $connection);

        // Local Mode
        $baseRoot = config('Storage')->fileManagerRoot;
        if (!is_dir($baseRoot)) mkdir($baseRoot, 0755, true);

        $userModel = new UserModel();
        $user = $userModel->getUser($username);
        
        if (!$user) {
            return new DeniedFileSystem('Authenticated user no longer exists.');
        }

        $vfs = new VirtualAdapter();
        $vfs->mount('Home', self::createLocalAdapter('Home', $user));
        $vfs->mount('Shared', self::createLocalAdapter('Shared', $user));

        // --- Custom Mounts ---
        try {
            $mountService = new \App\Services\MountService();
            $mounts = $mountService->getUserMounts($username, true);
            foreach ($mounts as $mount) {
                $mountId = (string)($mount['id'] ?? '');
                $mountName = (string)($mount['name'] ?? '');
                try {
                    if ($mountName === '' || !isset($mount['type']) || !is_array($mount['config'] ?? null)) {
                        throw new \RuntimeException('Persisted mount record is malformed.');
                    }

                    $adapter = self::createMountAdapter($mount);
                    $vfs->mount($mountName, $adapter, ['is_external' => true, 'mount_id' => $mountId]);
                } catch (\Throwable $e) {
                    $message = "Mount " . ($mountName !== '' ? $mountName : ($mountId !== '' ? $mountId : '[unknown]'))
                        . ' was disabled: ' . $e->getMessage();
                    if ($mountId !== '') {
                        try {
                            $mountService->recordMountHealth($mountId, $username, false, $e->getMessage());
                        } catch (\Throwable $healthException) {
                            $message .= '; health record failed: ' . $healthException->getMessage();
                        }
                    }
                    log_message('error', $message);
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to load user mounts: ' . $e->getMessage());
        }

        // Common writable storage for every account; operators opt in explicitly.
        $publicPath = config('Storage')->root . '/public';
        if (self::publicMountEnabled() && is_dir($publicPath)) {
            $vfs->mount('Public', new LocalAdapter($publicPath));
        }

        return $vfs;
    }
    public static function publicMountEnabled(): bool
    {
        return filter_var(getenv('EXTPLORER_PUBLIC_MOUNT') ?: '0', FILTER_VALIDATE_BOOLEAN);
    }

    public static function localRoot(string $alias, array $user): string
    {
        return match ($alias) {
            'Home' => (new LocalAdapter(config('Storage')->fileManagerRoot))->resolvePath((string)($user['home_dir'] ?? '/')),
            'Shared' => config('Storage')->shared,
            'Public' => self::publicMountEnabled() ? config('Storage')->root . '/public'
                : throw new \RuntimeException('The Public mount is not enabled.'),
            default => throw new \RuntimeException('Unknown local storage namespace.'),
        };
    }

    public static function createLocalAdapter(string $alias, array $user): LocalAdapter
    {
        $root = self::localRoot($alias, $user);
        if ($alias !== 'Public' && !is_dir($root) && !mkdir($root, 0755, true) && !is_dir($root)) throw new \RuntimeException('Unable to create local storage.');
        return new LocalAdapter($root);
    }

    public static function createMountAdapter(array $mount): IFileSystem
    {
        $type = strtolower((string)($mount['type'] ?? ''));
        $config = $mount['config'] ?? [];
        if (!is_array($config)) throw new \RuntimeException('Persisted mount record is malformed.');
        if ($type === 'local') return new LocalAdapter((new \App\Services\MountService())->assertLocalPathAllowed((string)($config['path'] ?? '')));
        return self::createAdapter($type, $config);
    }

    public static function createAdapter(string $type, array $config): IFileSystem
    {
        if ($type === 'ftp' || $type === 'ftps') {
            return new FtpAdapter((string)($config['host'] ?? ''), (string)($config['user'] ?? ''),
                self::revealConnectionSecret((string)($config['pass'] ?? '')), (int)($config['port'] ?? 21),
                (string)($config['root'] ?? '/'), $type === 'ftps', (string)($config['tls_spki_pin'] ?? ''));
        }
        if ($type === 'sftp' || $type === 'ssh2') {
            return new Ssh2Adapter((string)($config['host'] ?? ''), (string)($config['user'] ?? ''),
                self::revealConnectionSecret((string)($config['pass'] ?? '')), (int)($config['port'] ?? 22),
                (string)($config['root'] ?? '/'), (string)($config['host_key_fingerprint'] ?? ''),
                self::revealConnectionSecret((string)($config['private_key'] ?? '')),
                self::revealConnectionSecret((string)($config['public_key'] ?? '')),
                self::revealConnectionSecret((string)($config['private_key_passphrase'] ?? '')));
        }
        throw new \RuntimeException('Persisted mount type is invalid.');
    }

}
