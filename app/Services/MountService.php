<?php

namespace App\Services;

use App\Services\VFS\FtpAdapter;
use App\Services\VFS\Ssh2Adapter;
use CodeIgniter\Encryption\EncrypterInterface;

class MountService
{
    private string $mountsFile;
    private bool $encryptionRawData;

    public function __construct()
    {
        $this->mountsFile = WRITEPATH . 'mounts.php';
        $this->encryptionRawData = (bool)config('Encryption')->rawData;
        
        // Migration/Init
        if (file_exists(WRITEPATH . 'mounts.json') && !file_exists($this->mountsFile)) {
            $data = json_decode(file_get_contents(WRITEPATH . 'mounts.json'), true) ?? [];
            $this->saveMounts($data);
            unlink(WRITEPATH . 'mounts.json');
        }

        if (!file_exists($this->mountsFile)) {
            $this->saveMounts([]);
        }
    }

    private function getMounts(): array
    {
        if (!file_exists($this->mountsFile)) return [];
        $content = file_get_contents($this->mountsFile);
        if (strpos($content, '<?php') === 0) {
            $content = substr($content, strpos($content, "\n") + 1);
        }
        $mounts = json_decode($content, true) ?? [];
        $mounts = $this->migrateMountSecrets($mounts);
        return $mounts;
    }

    private function saveMounts(array $mounts): void
    {
        $content = '<?php die("Access denied"); ?>' . PHP_EOL . json_encode($mounts, JSON_PRETTY_PRINT);
        file_put_contents($this->mountsFile, $content);
    }

    public function getUserMounts(string $username, bool $includeSecrets = false): array
    {
        $all = $this->getMounts();
        $filtered = array_filter($all, fn($m) => $m['user'] === $username);
        if ($includeSecrets) {
            return $this->decryptMountSecrets($filtered);
        }
        return $this->stripMountSecrets($filtered);
    }

    public function addMount(string $username, string $name, string $type, array $config): string
    {
        // Permission Check
        if (!can('mount_external') && !can('admin_users')) {
            throw new \Exception("Permission denied: Cannot mount external paths.");
        }

        // Sanitize Name
        $name = preg_replace('/[^a-zA-Z0-9 _-]/', '', $name);
        if (empty($name)) throw new \Exception("Invalid mount name.");

        // Type Validation
        $type = strtolower($type);
        if ($type === 'local') {
            $path = trim($config['path'] ?? '');
            if ($path === '') {
                throw new \Exception("Local path is required.");
            }

            // Auto-convert WSL paths if running on Windows
            if (DIRECTORY_SEPARATOR === '\\' && preg_match('|^/mnt/([a-z])/(.*)|i', $path, $matches)) {
                $path = strtoupper($matches[1]) . ':/' . $matches[2];
                $config['path'] = $path;
            }

            error_log("Checking local mount path: '$path'");
            $realPath = realpath($path);
            if (!$realPath || !is_dir($realPath)) {
                // Should we allow mounting non-existent paths? Maybe creation later? 
                // For now, strict validation.
                throw new \Exception("Local path does not exist or is not readable: $path");
            }

            $settingsService = new SettingsService();
            $allowedRoots = array_merge(
                config('App')->mountRootAllowlist ?? [],
                $settingsService->get('mount_root_allowlist', [])
            );
            $allowedRoots = array_values(array_filter($allowedRoots, static fn($root) => is_string($root) && $root !== ''));
            if (empty($allowedRoots)) {
                throw new \Exception("External mounts are disabled. Configure mountRootAllowlist.");
            }

            $isAllowed = false;
            foreach ($allowedRoots as $root) {
                $rootReal = realpath($root);
                if (!$rootReal) {
                    continue;
                }
                $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                if (str_starts_with($realPath . DIRECTORY_SEPARATOR, $rootReal)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                throw new \Exception("Local path is not within an allowlisted mount root.");
            }
        } elseif ($type === 'ftp' || $type === 'sftp') {
            $host = trim((string)($config['host'] ?? ''));
            $user = trim((string)($config['user'] ?? ''));
            $pass = (string)($config['pass'] ?? '');
            $port = (int)($config['port'] ?? ($type === 'sftp' ? 22 : 21));
            $root = trim((string)($config['root'] ?? '/'));

            if ($host === '') throw new \Exception("Remote host is required.");
            if ($user === '') throw new \Exception("Remote username is required.");
            if ($pass === '') throw new \Exception("Remote password is required.");
            if ($port < 1 || $port > 65535) throw new \Exception("Remote port is invalid.");
            if (!$this->canEncrypt()) {
                throw new \Exception("Encryption key not configured. Set Config\\Encryption::\$key before adding remote mounts.");
            }

            $config['host'] = $host;
            $config['user'] = $user;
            $config['pass'] = $this->encryptSecret($pass);
            $config['port'] = $port;
            $config['root'] = $root === '' ? '/' : $root;

            // Validate connectivity/auth
            if ($type === 'ftp') {
                new FtpAdapter($host, $user, $pass, $port, $config['root']);
            } else {
                new Ssh2Adapter($host, $user, $pass, $port, $config['root']);
            }
        } else {
            throw new \Exception("Unknown mount type.");
        }

        $id = uniqid('mnt_');
        $mounts = $this->getMounts();
        
        $mounts[$id] = [
            'id' => $id,
            'user' => $username,
            'name' => $name,
            'type' => $type,
            'config' => $config,
            'created_at' => time()
        ];

        $this->saveMounts($mounts);
        return $id;
    }

    public function removeMount(string $id, string $username): bool
    {
        $mounts = $this->getMounts();
        if (!isset($mounts[$id])) return false;

        $mount = $mounts[$id];
        
        // Ownership check (or admin)
        if ($mount['user'] !== $username && !can('admin_users')) {
            throw new \Exception("Permission denied.");
        }

        unset($mounts[$id]);
        $this->saveMounts($mounts);
        return true;
    }

    private function canEncrypt(): bool
    {
        $key = (string)config('Encryption')->key;
        return $key !== '';
    }

    private function getEncrypter(): EncrypterInterface
    {
        return \Config\Services::encrypter();
    }

    private function isEncryptedSecret($value): bool
    {
        return is_string($value) && str_starts_with($value, 'enc:');
    }

    private function encodeCiphertext(string $ciphertext): string
    {
        return $this->encryptionRawData ? base64_encode($ciphertext) : $ciphertext;
    }

    private function decodeCiphertext(string $ciphertext): string
    {
        if ($this->encryptionRawData) {
            $decoded = base64_decode($ciphertext, true);
            return $decoded === false ? '' : $decoded;
        }
        return $ciphertext;
    }

    private function encryptSecret(string $value): string
    {
        if ($value === '') return '';
        $encrypted = $this->getEncrypter()->encrypt($value);
        return 'enc:' . $this->encodeCiphertext($encrypted);
    }

    private function decryptSecret(string $value): string
    {
        if (!$this->isEncryptedSecret($value)) return $value;
        $payload = substr($value, 4);
        $decoded = $this->decodeCiphertext($payload);
        if ($decoded === '') {
            log_message('error', 'Failed to decode encrypted mount secret');
            return '';
        }
        try {
            return (string)$this->getEncrypter()->decrypt($decoded);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to decrypt mount secret: ' . $e->getMessage());
            return '';
        }
    }

    private function migrateMountSecrets(array $mounts): array
    {
        if (!$this->canEncrypt()) {
            return $mounts;
        }
        $dirty = false;
        foreach ($mounts as $id => $mount) {
            $type = strtolower((string)($mount['type'] ?? ''));
            if (!in_array($type, ['ftp', 'sftp', 'ssh2'], true)) continue;
            $pass = $mount['config']['pass'] ?? null;
            if (is_string($pass) && $pass !== '' && !$this->isEncryptedSecret($pass)) {
                $mounts[$id]['config']['pass'] = $this->encryptSecret($pass);
                $dirty = true;
            }
        }
        if ($dirty) {
            $this->saveMounts($mounts);
        }
        return $mounts;
    }

    private function decryptMountSecrets(array $mounts): array
    {
        foreach ($mounts as $id => $mount) {
            $type = strtolower((string)($mount['type'] ?? ''));
            if (!in_array($type, ['ftp', 'sftp', 'ssh2'], true)) continue;
            $pass = $mount['config']['pass'] ?? null;
            if (is_string($pass) && $pass !== '') {
                $mounts[$id]['config']['pass'] = $this->decryptSecret($pass);
            }
        }
        return $mounts;
    }

    private function stripMountSecrets(array $mounts): array
    {
        foreach ($mounts as $id => $mount) {
            $type = strtolower((string)($mount['type'] ?? ''));
            if (!in_array($type, ['ftp', 'sftp', 'ssh2'], true)) continue;
            if (isset($mounts[$id]['config']['pass'])) {
                unset($mounts[$id]['config']['pass']);
            }
        }
        return $mounts;
    }
}
