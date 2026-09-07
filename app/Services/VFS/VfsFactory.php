<?php

namespace App\Services\VFS;

use App\Models\UserModel;

class VfsFactory
{
    private static function revealConnectionSecret(string $secret): string
    {
        if ($secret === '') {
            return '';
        }

        if (!str_starts_with($secret, 'enc:')) {
            return $secret;
        }

        $payload = substr($secret, 4);
        if ((bool)config('Encryption')->rawData) {
            $decoded = base64_decode($payload, true);
            if ($decoded === false) {
                return '';
            }
            $payload = $decoded;
        }

        try {
            return (string)\Config\Services::encrypter()->decrypt($payload);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function createFileSystem(?string $username = null, array $connection = []): IFileSystem
    {
        if ($username === null || trim($username) === '') {
            return new DeniedFileSystem();
        }

        $mode = $connection['mode'] ?? 'local';

        if ($mode === 'ftp' || $mode === 'ftps') {
            $password = self::revealConnectionSecret((string)($connection['pass'] ?? ''));
            return new FtpAdapter(
                $connection['host'],
                $connection['user'],
                $password,
                $connection['port'],
                '/',
                $mode === 'ftps'
            );
        }

        if ($mode === 'sftp') {
            $password = self::revealConnectionSecret((string)($connection['pass'] ?? ''));
            return new Ssh2Adapter(
                $connection['host'],
                $connection['user'],
                $password,
                $connection['port'],
                '/',
                (string)($connection['host_key_fingerprint'] ?? '')
            );
        }

        // Local Mode
        $baseRoot = config('Storage')->fileManagerRoot;
        if (!is_dir($baseRoot)) mkdir($baseRoot, 0755, true);

        $userModel = new UserModel();
        $user = $userModel->getUser($username);
        
        if (!$user) {
            return new DeniedFileSystem('Authenticated user no longer exists.');
        }

        // Determine Home Path
        // Legacy compatibility: If home_dir is set, use it. 
        // If it is '/', it maps to baseRoot.
        $userHome = $user['home_dir'] ?? '/';
        
        $homePath = (new LocalAdapter($baseRoot))->resolvePath($userHome);

        if (!is_dir($homePath)) {
            mkdir($homePath, 0755, true);
        }

        // Create Virtual Adapter
        $vfs = new VirtualAdapter();
        
        // Mount Home
        $vfs->mount('Home', new LocalAdapter($homePath));

        // Mount Shared
        $sharedPath = config('Storage')->shared;
        if (!is_dir($sharedPath)) mkdir($sharedPath, 0755, true);
        $vfs->mount('Shared', new LocalAdapter($sharedPath));
        
        // --- Custom Mounts ---
        try {
            $mountService = new \App\Services\MountService();
            $mounts = $mountService->getUserMounts($username, true);
            foreach ($mounts as $mount) {
                try {
                    if ($mount['type'] === 'local') {
                        $adapter = new LocalAdapter($mount['config']['path']);
                        $vfs->mount($mount['name'], $adapter, ['is_external' => true]);
                    }
                    if ($mount['type'] === 'ftp' || $mount['type'] === 'ftps') {
                        $config = $mount['config'] ?? [];
                        $adapter = new FtpAdapter(
                            $config['host'] ?? '',
                            $config['user'] ?? '',
                            $config['pass'] ?? '',
                            (int)($config['port'] ?? 21),
                            $config['root'] ?? '/',
                            $mount['type'] === 'ftps'
                        );
                        $vfs->mount($mount['name'], $adapter, ['is_external' => true]);
                    }
                    if ($mount['type'] === 'sftp' || $mount['type'] === 'ssh2') {
                        $config = $mount['config'] ?? [];
                        $adapter = new Ssh2Adapter(
                            $config['host'] ?? '',
                            $config['user'] ?? '',
                            $config['pass'] ?? '',
                            (int)($config['port'] ?? 22),
                            $config['root'] ?? '/',
                            (string)($config['host_key_fingerprint'] ?? '')
                        );
                        $vfs->mount($mount['name'], $adapter, ['is_external' => true]);
                    }
                } catch (\Exception $e) {
                    // Log invalid mount but don't crash
                    log_message('error', "Failed to load mount {$mount['name']}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to load user mounts: ' . $e->getMessage());
        }

        // Optional: Mount Public if it exists
        $publicPath = config('Storage')->root . '/public';
        if (is_dir($publicPath)) {
            $vfs->mount('Public', new LocalAdapter($publicPath));
        }

        return $vfs;
    }
}
