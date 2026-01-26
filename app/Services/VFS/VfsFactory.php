<?php

namespace App\Services\VFS;

use App\Models\UserModel;

class VfsFactory
{
    public static function createFileSystem(?string $username = null, array $connection = []): IFileSystem
    {
        $mode = $connection['mode'] ?? 'local';

        if ($mode === 'ftp') {
            return new FtpAdapter(
                $connection['host'],
                $connection['user'],
                $connection['pass'],
                $connection['port']
            );
        }

        if ($mode === 'sftp') {
            return new Ssh2Adapter(
                $connection['host'],
                $connection['user'],
                $connection['pass'],
                $connection['port']
            );
        }

        // Local Mode
        $baseRoot = WRITEPATH . 'file_manager_root';
        if (!is_dir($baseRoot)) mkdir($baseRoot, 0755, true);

        // If no user provided (e.g. public access?), just return root adapter?
        // Or if we are in a context without a user model.
        if (!$username) {
            return new LocalAdapter($baseRoot);
        }

        $userModel = new UserModel();
        $user = $userModel->getUser($username);
        
        // Fallback if user not found (shouldn't happen in auth context)
        if (!$user) {
            return new LocalAdapter($baseRoot);
        }

        // Determine Home Path
        // Legacy compatibility: If home_dir is set, use it. 
        // If it is '/', it maps to baseRoot.
        $userHome = $user['home_dir'] ?? '/';
        
        // Sanitize
        $userHome = str_replace('..', '', $userHome);
        $userHome = trim($userHome, '/\\');

        $homePath = $baseRoot;
        if ($userHome) {
            $homePath .= DIRECTORY_SEPARATOR . $userHome;
        }

        if (!is_dir($homePath)) {
            mkdir($homePath, 0755, true);
        }

        // Create Virtual Adapter
        $vfs = new VirtualAdapter();
        
        // Mount Home
        $vfs->mount('Home', new LocalAdapter($homePath));

        // Mount Shared
        $sharedPath = WRITEPATH . 'shared';
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
                    if ($mount['type'] === 'ftp') {
                        $config = $mount['config'] ?? [];
                        $adapter = new FtpAdapter(
                            $config['host'] ?? '',
                            $config['user'] ?? '',
                            $config['pass'] ?? '',
                            (int)($config['port'] ?? 21),
                            $config['root'] ?? '/'
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
                            $config['root'] ?? '/'
                        );
                        $vfs->mount($mount['name'], $adapter, ['is_external' => true]);
                    }
                } catch (\Exception $e) {
                    // Log invalid mount but don't crash
                    log_message('error', "Failed to load mount {$mount['name']}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {}

        // Optional: Mount Public if it exists
        $publicPath = WRITEPATH . 'public';
        if (is_dir($publicPath)) {
            $vfs->mount('Public', new LocalAdapter($publicPath));
        }

        return $vfs;
    }
}
