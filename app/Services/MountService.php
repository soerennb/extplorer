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

    public function getMountForUser(string $id, string $username, bool $includeSecrets = false): array
    {
        $mounts = $this->getMounts();
        if (!isset($mounts[$id])) {
            throw new \Exception("Mount not found.");
        }

        $mount = $mounts[$id];
        if ($mount['user'] !== $username && !can('admin_users')) {
            throw new \Exception("Permission denied.");
        }

        if ($includeSecrets) {
            $decrypted = $this->decryptMountSecrets([$id => $mount]);
            return $decrypted[$id];
        }

        $stripped = $this->stripMountSecrets([$id => $mount]);
        return $stripped[$id];
    }

    public function addMount(string $username, string $name, string $type, array $config): string
    {
        // Permission Check
        if (!can('mount_external') && !can('admin_users')) {
            throw new \Exception("Permission denied: Cannot mount external paths.");
        }

        $name = $this->sanitizeMountName($name);
        [$type, $config] = $this->validateAndNormalizeMount($type, $config);
        $this->validateConnectivity($type, $config);

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

    public function updateMount(string $id, string $username, string $name, string $type, array $config): array
    {
        if (!can('mount_external') && !can('admin_users')) {
            throw new \Exception("Permission denied: Cannot mount external paths.");
        }

        $mounts = $this->getMounts();
        if (!isset($mounts[$id])) {
            throw new \Exception("Mount not found.");
        }

        $existing = $mounts[$id];
        if ($existing['user'] !== $username && !can('admin_users')) {
            throw new \Exception("Permission denied.");
        }

        $name = $this->sanitizeMountName($name);
        $existingEncryptedPass = (string)($existing['config']['pass'] ?? '');
        [$type, $config] = $this->validateAndNormalizeMount($type, $config, $existingEncryptedPass, true);
        $this->validateConnectivity($type, $config);

        $mounts[$id] = [
            'id' => $id,
            'user' => $existing['user'],
            'name' => $name,
            'type' => $type,
            'config' => $config,
            'created_at' => $existing['created_at'] ?? time(),
            'updated_at' => time(),
        ];

        $this->saveMounts($mounts);
        $stripped = $this->stripMountSecrets([$id => $mounts[$id]]);
        return $stripped[$id];
    }

    public function testMount(string $username, ?string $id, string $name, string $type, array $config): array
    {
        if (!can('mount_external') && !can('admin_users')) {
            throw new \Exception("Permission denied: Cannot mount external paths.");
        }

        $existingEncryptedPass = '';
        if ($id) {
            $existing = $this->getMountForUser($id, $username, true);
            $existingEncryptedPass = (string)($existing['config']['pass'] ?? '');
        }

        $name = $this->sanitizeMountName($name);
        [$type, $config] = $this->validateAndNormalizeMount($type, $config, $existingEncryptedPass, true);
        $this->validateConnectivity($type, $config);

        $configForResponse = $config;
        if (isset($configForResponse['pass'])) {
            unset($configForResponse['pass']);
        }

        return [
            'status' => 'success',
            'name' => $name,
            'type' => $type,
            'config' => $configForResponse,
        ];
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
            $hasPass = isset($mounts[$id]['config']['pass']) && (string)$mounts[$id]['config']['pass'] !== '';
            $mounts[$id]['has_pass'] = $hasPass;
            if ($hasPass) {
                unset($mounts[$id]['config']['pass']);
            }
        }
        return $mounts;
    }

    private function sanitizeMountName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9 _-]/', '', $name);
        if (empty($name)) {
            throw new \Exception("Invalid mount name.");
        }
        return $name;
    }

    private function validateAndNormalizeMount(
        string $type,
        array $config,
        string $existingEncryptedPass = '',
        bool $allowExistingPass = false
    ): array {
        $type = strtolower($type);

        if ($type === 'local') {
            $path = trim((string)($config['path'] ?? ''));
            if ($path === '') {
                throw new \Exception("Local path is required.");
            }

            if (DIRECTORY_SEPARATOR === '\\' && preg_match('|^/mnt/([a-z])/(.*)|i', $path, $matches)) {
                $path = strtoupper($matches[1]) . ':/' . $matches[2];
            }

            $realPath = realpath($path);
            if (!$realPath || !is_dir($realPath)) {
                throw new \Exception("Local path does not exist or is not readable: $path");
            }

            $this->assertLocalPathAllowlisted($realPath);
            $config['path'] = $realPath;
            return [$type, $config];
        }

        if ($type === 'ftp' || $type === 'sftp') {
            $host = trim((string)($config['host'] ?? ''));
            $user = trim((string)($config['user'] ?? ''));
            $passInput = (string)($config['pass'] ?? '');
            $portDefault = $type === 'sftp' ? 22 : 21;
            $port = (int)($config['port'] ?? $portDefault);
            $root = trim((string)($config['root'] ?? '/'));

            if ($host === '') {
                throw new \Exception("Remote host is required.");
            }
            if ($user === '') {
                throw new \Exception("Remote username is required.");
            }
            if ($port < 1 || $port > 65535) {
                throw new \Exception("Remote port is invalid.");
            }
            if (!$this->canEncrypt()) {
                throw new \Exception("Encryption key not configured. Set Config\\Encryption::\$key before adding remote mounts.");
            }

            $plainPass = $passInput;
            $encryptedPass = '';
            if ($plainPass !== '') {
                $encryptedPass = $this->encryptSecret($plainPass);
            } elseif ($allowExistingPass && $existingEncryptedPass !== '') {
                $plainPass = $this->decryptSecret($existingEncryptedPass);
                $encryptedPass = $existingEncryptedPass;
            }

            if ($plainPass === '') {
                throw new \Exception("Remote password is required.");
            }

            $config['host'] = $host;
            $config['user'] = $user;
            $config['port'] = $port;
            $config['root'] = $root === '' ? '/' : $root;
            $config['pass'] = $encryptedPass;
            $config['__plain_pass'] = $plainPass;
            return [$type, $config];
        }

        throw new \Exception("Unknown mount type.");
    }

    private function assertLocalPathAllowlisted(string $realPath): void
    {
        $settingsService = new SettingsService();
        $allowedRoots = array_merge(
            config('App')->mountRootAllowlist ?? [],
            $settingsService->get('mount_root_allowlist', [])
        );
        $allowedRoots = array_values(array_filter(
            $allowedRoots,
            static fn($root) => is_string($root) && $root !== ''
        ));
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
    }

    private function validateConnectivity(string $type, array &$config): void
    {
        if ($type === 'local') {
            return;
        }

        $plainPass = (string)($config['__plain_pass'] ?? '');
        unset($config['__plain_pass']);

        $host = (string)($config['host'] ?? '');
        $user = (string)($config['user'] ?? '');
        $port = (int)($config['port'] ?? ($type === 'sftp' ? 22 : 21));
        $root = (string)($config['root'] ?? '/');

        if ($type === 'ftp') {
            new FtpAdapter($host, $user, $plainPass, $port, $root);
            return;
        }

        new Ssh2Adapter($host, $user, $plainPass, $port, $root);
    }
}
