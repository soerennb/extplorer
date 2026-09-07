<?php

namespace App\Services;

use App\Services\VFS\FtpAdapter;
use App\Services\VFS\PathPolicy;
use App\Services\VFS\Ssh2Adapter;
use App\Services\RemoteSecurityPolicy;
use CodeIgniter\Encryption\EncrypterInterface;

class MountService
{
    private string $mountsFile;
    private bool $encryptionRawData;

    public function __construct()
    {
        $this->mountsFile = config('Storage')->state . '/mounts.php';
        $this->encryptionRawData = (bool)config('Encryption')->rawData;

        if (!AtomicFileStore::exists($this->mountsFile)) {
            $this->saveMounts([]);
        }
    }

    private function getMounts(): array
    {
        if (!AtomicFileStore::exists($this->mountsFile)) return [];
        return AtomicFileStore::read($this->mountsFile);
    }

    private function saveMounts(array $mounts): void
    {
        AtomicFileStore::write($this->mountsFile, $mounts);
    }

    /**
     * Encrypts legacy plaintext remote mount passwords during maintenance.
     */
    public function migrateSecrets(): void
    {
        if (!$this->canEncrypt()) {
            return;
        }

        AtomicFileStore::transaction($this->mountsFile, function (array &$mounts): void {
            foreach ($mounts as $id => $mount) {
                $type = strtolower((string)($mount['type'] ?? ''));
                if (!in_array($type, ['ftp', 'ftps', 'sftp', 'ssh2'], true)) {
                    continue;
                }
                foreach (['pass', 'private_key', 'public_key', 'private_key_passphrase'] as $secretName) {
                    $secret = $mount['config'][$secretName] ?? null;
                    if (is_string($secret) && $secret !== '' && !$this->isEncryptedSecret($secret)) {
                        $mounts[$id]['config'][$secretName] = $this->encryptSecret($secret);
                    }
                }
            }
        });
    }

    public function getUserMounts(string $username, bool $includeSecrets = false): array
    {
        $all = $this->getMounts();
        $filtered = [];
        foreach ($all as $id => $mount) {
            if (!is_array($mount) || ($mount['user'] ?? null) !== $username) {
                continue;
            }

            // Preserve the storage key as the ID for old records that did not
            // persist it inside the record itself. This also lets VfsFactory
            // report malformed legacy mounts through the normal health API.
            $mount['id'] = (string)($mount['id'] ?? $id);
            $filtered[$id] = $mount;
        }
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

    /** Return the last observed mount state without contacting the endpoint. */
    public function getMountHealth(string $id, string $username): array
    {
        $mount = $this->getMountForUser($id, $username, false);
        $health = is_array($mount['health'] ?? null) ? $mount['health'] : [];
        return [
            'id' => $mount['id'] ?? $id,
            'name' => $mount['name'] ?? '',
            'type' => $mount['type'] ?? '',
            'status' => (string)($health['status'] ?? 'unknown'),
            'checked_at' => (int)($health['checked_at'] ?? 0),
            'error' => (string)($health['error'] ?? ''),
        ];
    }

    public function recordMountHealth(string $id, string $username, bool $healthy, string $error = ''): void
    {
        AtomicFileStore::transaction($this->mountsFile, function (array &$mounts) use ($id, $username, $healthy, $error): void {
            if (!isset($mounts[$id]) || (($mounts[$id]['user'] ?? '') !== $username && !can('admin_users'))) {
                return;
            }
            $mounts[$id]['health'] = [
                'status' => $healthy ? 'healthy' : 'unhealthy',
                'checked_at' => time(),
                'error' => substr($error, 0, 500),
            ];
        });
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

        return AtomicFileStore::transaction($this->mountsFile, function (array &$mounts) use ($username, $name, $type, $config): string {
            $this->assertMountNameAvailable($mounts, $username, $name);
            $id = 'mnt_' . bin2hex(random_bytes(12));
            $mounts[$id] = [
                'id' => $id,
                'user' => $username,
                'name' => $name,
                'type' => $type,
                'config' => $config,
                'created_at' => time(),
                'health' => ['status' => 'healthy', 'checked_at' => time(), 'error' => ''],
            ];
            return $id;
        });
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
        $this->assertMountNameAvailable($mounts, $existing['user'], $name, $id);
        $existingConfig = is_array($existing['config'] ?? null) ? $existing['config'] : [];
        [$type, $config] = $this->validateAndNormalizeMount($type, $config, $existingConfig, true);
        $this->validateConnectivity($type, $config);

        $updated = AtomicFileStore::transaction($this->mountsFile, function (array &$mounts) use ($id, $username, $name, $type, $config): array {
            if (!isset($mounts[$id])) {
                throw new \Exception("Mount not found.");
            }
            $current = $mounts[$id];
            if (($current['user'] ?? '') !== $username && !can('admin_users')) {
                throw new \Exception("Permission denied.");
            }
            $this->assertMountNameAvailable($mounts, (string)$current['user'], $name, $id);
            $mounts[$id] = [
                'id' => $id,
                'user' => $current['user'],
                'name' => $name,
                'type' => $type,
                'config' => $config,
                'created_at' => $current['created_at'] ?? time(),
                'updated_at' => time(),
                'health' => ['status' => 'healthy', 'checked_at' => time(), 'error' => ''],
            ];
            return $mounts[$id];
        });
        $stripped = $this->stripMountSecrets([$id => $updated]);
        return $stripped[$id];
    }

    public function testMount(string $username, ?string $id, string $name, string $type, array $config): array
    {
        if (!can('mount_external') && !can('admin_users')) {
            throw new \Exception("Permission denied: Cannot mount external paths.");
        }

        $existingConfig = [];
        if ($id) {
            $existing = $this->getStoredMountForUser($id, $username);
            $existingConfig = is_array($existing['config'] ?? null) ? $existing['config'] : [];
        }

        $name = $this->sanitizeMountName($name);
        $mounts = $this->getMounts();
        $this->assertMountNameAvailable($mounts, $username, $name, $id);
        [$type, $config] = $this->validateAndNormalizeMount($type, $config, $existingConfig, true);
        $this->validateConnectivity($type, $config);

        $configForResponse = $config;
        foreach (['pass', 'private_key', 'public_key', 'private_key_passphrase'] as $secretName) {
            unset($configForResponse[$secretName]);
        }

        if ($id !== null) {
            $this->recordMountHealth($id, $username, true);
        }

        return [
            'status' => 'success',
            'name' => $name,
            'type' => $type,
            'config' => $configForResponse,
            'has_pass' => !empty($config['pass']),
            'has_private_key' => !empty($config['private_key']),
            'has_public_key' => !empty($config['public_key']),
        ];
    }

    public function removeMount(string $id, string $username): bool
    {
        return AtomicFileStore::transaction($this->mountsFile, function (array &$mounts) use ($id, $username): bool {
            if (!isset($mounts[$id])) return false;

            $mount = $mounts[$id];
            if (($mount['user'] ?? '') !== $username && !can('admin_users')) {
                throw new \Exception("Permission denied.");
            }

            unset($mounts[$id]);
            return true;
        });
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

    private function getStoredMountForUser(string $id, string $username): array
    {
        $mounts = $this->getMounts();
        if (!isset($mounts[$id])) {
            throw new \Exception('Mount not found.');
        }

        $mount = $mounts[$id];
        if (($mount['user'] ?? '') !== $username && !can('admin_users')) {
            throw new \Exception('Permission denied.');
        }

        return $mount;
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

    private function decryptMountSecrets(array $mounts): array
    {
        foreach ($mounts as $id => $mount) {
            $type = strtolower((string)($mount['type'] ?? ''));
            if (!in_array($type, ['ftp', 'ftps', 'sftp', 'ssh2'], true)) continue;
            $pass = $mount['config']['pass'] ?? null;
            if (is_string($pass) && $pass !== '') {
                $mounts[$id]['config']['pass'] = $this->decryptSecret($pass);
            }
            foreach (['private_key', 'public_key', 'private_key_passphrase'] as $secretName) {
                $secret = $mount['config'][$secretName] ?? null;
                if (is_string($secret) && $secret !== '') {
                    $mounts[$id]['config'][$secretName] = $this->decryptSecret($secret);
                }
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
            $hasPrivateKey = isset($mounts[$id]['config']['private_key'])
                && (string)$mounts[$id]['config']['private_key'] !== '';
            $mounts[$id]['has_private_key'] = $hasPrivateKey;
            $hasPublicKey = isset($mounts[$id]['config']['public_key'])
                && (string)$mounts[$id]['config']['public_key'] !== '';
            $mounts[$id]['has_public_key'] = $hasPublicKey;
            foreach (['private_key', 'public_key', 'private_key_passphrase'] as $secretName) {
                unset($mounts[$id]['config'][$secretName]);
            }
        }
        return $mounts;
    }

    private function sanitizeMountName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9 _-]/', '', $name) ?? '';
        $name = trim($name);
        if ($name === '') {
            throw new \Exception("Invalid mount name.");
        }

        try {
            PathPolicy::normalizeMountAlias($name);
        } catch (\RuntimeException $exception) {
            throw new \Exception($exception->getMessage(), 0, $exception);
        }

        return $name;
    }

    private function isReservedMountName(string $name): bool
    {
        $reserved = ['Home', 'Shared', 'Public'];
        foreach ($reserved as $item) {
            if (strcasecmp($name, $item) === 0) {
                return true;
            }
        }
        return false;
    }

    private function assertMountNameAvailable(array $mounts, string $username, string $name, ?string $ignoreId = null): void
    {
        if ($this->isReservedMountName($name)) {
            throw new \Exception("This mount name is reserved.");
        }

        foreach ($mounts as $id => $mount) {
            if ($ignoreId !== null && $id === $ignoreId) {
                continue;
            }
            if (($mount['user'] ?? '') !== $username) {
                continue;
            }
            if (strcasecmp((string)($mount['name'] ?? ''), $name) === 0) {
                throw new \Exception("A mount with this name already exists.");
            }
        }
    }

    private function validateAndNormalizeMount(
        string $type,
        array $config,
        array $existingConfig = [],
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

        if ($type === 'ssh2') {
            $type = 'sftp';
        }

        if (in_array($type, ['ftp', 'ftps', 'sftp'], true)) {
            $host = strtolower(trim((string)($config['host'] ?? '')));
            $user = trim((string)($config['user'] ?? ''));
            $passInput = (string)($config['pass'] ?? '');
            $authMethod = strtolower(trim((string)($config['auth_method'] ?? ($existingConfig['auth_method'] ?? 'password'))));
            $privateKeyInput = (string)($config['private_key'] ?? '');
            $publicKeyInput = (string)($config['public_key'] ?? '');
            $passphraseInput = (string)($config['private_key_passphrase'] ?? '');
            $portDefault = $type === 'sftp' ? 22 : ($type === 'ftps' ? 990 : 21);
            $port = (int)($config['port'] ?? $portDefault);
            $root = trim((string)($config['root'] ?? '/'));

            if ($host === '') {
                throw new \Exception("Remote host is required.");
            }
            if ($user === '') {
                throw new \Exception("Remote username is required.");
            }
            if (!in_array($authMethod, ['password', 'private_key'], true)) {
                throw new \Exception('Remote authentication method is invalid.');
            }
            if ($type !== 'sftp' && $authMethod !== 'password') {
                throw new \Exception('Private-key authentication is only supported for SFTP.');
            }
            if ($port < 1 || $port > 65535) {
                throw new \Exception("Remote port is invalid.");
            }
            if (!$this->canEncrypt()) {
                throw new \Exception("Encryption key not configured. Set Config\\Encryption::\$key before adding remote mounts.");
            }

            $storedPass = (string)($existingConfig['pass'] ?? '');
            $storedPrivateKey = (string)($existingConfig['private_key'] ?? '');
            $storedPublicKey = (string)($existingConfig['public_key'] ?? '');
            $storedPassphrase = (string)($existingConfig['private_key_passphrase'] ?? '');

            $plainPass = $passInput;
            $encryptedPass = '';
            if ($plainPass !== '') {
                $encryptedPass = $this->encryptSecret($plainPass);
            } elseif ($allowExistingPass && $storedPass !== '') {
                $plainPass = $this->decryptSecret($storedPass);
                $encryptedPass = $storedPass;
            }

            $plainPrivateKey = $privateKeyInput;
            $encryptedPrivateKey = '';
            if ($plainPrivateKey !== '') {
                $encryptedPrivateKey = $this->encryptSecret($plainPrivateKey);
            } elseif ($allowExistingPass && $storedPrivateKey !== '') {
                $plainPrivateKey = $this->decryptSecret($storedPrivateKey);
                $encryptedPrivateKey = $storedPrivateKey;
            }

            $plainPublicKey = $publicKeyInput;
            $encryptedPublicKey = '';
            if ($plainPublicKey !== '') {
                $encryptedPublicKey = $this->encryptSecret($plainPublicKey);
            } elseif ($allowExistingPass && $storedPublicKey !== '') {
                $plainPublicKey = $this->decryptSecret($storedPublicKey);
                $encryptedPublicKey = $storedPublicKey;
            }

            $plainPassphrase = $passphraseInput;
            $encryptedPassphrase = '';
            if ($plainPassphrase !== '') {
                $encryptedPassphrase = $this->encryptSecret($plainPassphrase);
            } elseif ($allowExistingPass && $storedPassphrase !== '') {
                $plainPassphrase = $this->decryptSecret($storedPassphrase);
                $encryptedPassphrase = $storedPassphrase;
            }

            if ($authMethod === 'password' && $plainPass === '') {
                throw new \Exception("Remote password is required.");
            }
            if ($authMethod === 'private_key' && ($plainPrivateKey === '' || $plainPublicKey === '')) {
                throw new \Exception('SFTP private and public keys are required.');
            }
            if (strlen($plainPrivateKey) > 1024 * 1024 || strlen($plainPublicKey) > 1024 * 1024) {
                throw new \Exception('SFTP key material is too large.');
            }

            $config['host'] = $host;
            $config['user'] = $user;
            $config['port'] = $port;
            $config['root'] = $root === '' ? '/' : $root;
            $config['auth_method'] = $authMethod;
            if ($type === 'sftp') {
                $config['host_key_fingerprint'] = trim((string)($config['host_key_fingerprint'] ?? ''));
                $config['tls_spki_pin'] = '';
            } elseif ($type === 'ftps') {
                $rawPin = trim((string)($config['tls_spki_pin'] ?? ''));
                $config['tls_spki_pin'] = (new RemoteSecurityPolicy())->normalizeTlsSpkiPin($rawPin);
                if ($rawPin !== '' && $config['tls_spki_pin'] === '') {
                    throw new \Exception('FTPS SPKI pin must be a SHA-256 fingerprint or sha256/ base64 pin.');
                }
                unset($config['host_key_fingerprint']);
            } else {
                unset($config['host_key_fingerprint']);
                unset($config['tls_spki_pin']);
            }
            (new RemoteEndpointPolicy())->authorize(
                $type,
                $host,
                $port,
                (string)($config['host_key_fingerprint'] ?? ''),
                $type === 'ftps'
            );
            $config['pass'] = $encryptedPass;
            $config['private_key'] = $encryptedPrivateKey;
            $config['public_key'] = $encryptedPublicKey;
            $config['private_key_passphrase'] = $encryptedPassphrase;
            $config['__plain_pass'] = $plainPass;
            $config['__plain_private_key'] = $plainPrivateKey;
            $config['__plain_public_key'] = $plainPublicKey;
            $config['__plain_passphrase'] = $plainPassphrase;
            return [$type, $config];
        }

        throw new \Exception("Unknown mount type.");
    }

    private function assertLocalPathAllowlisted(string $realPath): void
    {
        $settingsService = new SettingsService();
        $allowedRoots = array_merge(
            [config('Storage')->fileManagerRoot],
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

        foreach ($allowedRoots as $root) {
            $rootReal = realpath($root);
            if (!$rootReal) {
                continue;
            }
            $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (str_starts_with($realPath . DIRECTORY_SEPARATOR, $rootReal)) {
                return;
            }
        }

        throw new \Exception("Local path is not within an allowlisted mount root.");
    }

    private function validateConnectivity(string $type, array &$config): void
    {
        if ($type === 'local') {
            return;
        }

        $plainPass = (string)($config['__plain_pass'] ?? '');
        $plainPrivateKey = (string)($config['__plain_private_key'] ?? '');
        $plainPublicKey = (string)($config['__plain_public_key'] ?? '');
        $plainPassphrase = (string)($config['__plain_passphrase'] ?? '');
        unset($config['__plain_pass']);
        unset($config['__plain_private_key'], $config['__plain_public_key'], $config['__plain_passphrase']);

        $host = (string)($config['host'] ?? '');
        $user = (string)($config['user'] ?? '');
        $port = (int)($config['port'] ?? ($type === 'sftp' ? 22 : 21));
        $root = (string)($config['root'] ?? '/');

        if ($type === 'ftp' || $type === 'ftps') {
            new FtpAdapter($host, $user, $plainPass, $port, $root, $type === 'ftps', (string)($config['tls_spki_pin'] ?? ''));
            return;
        }

        new Ssh2Adapter(
            $host,
            $user,
            $plainPass,
            $port,
            $root,
            (string)($config['host_key_fingerprint'] ?? ''),
            $plainPrivateKey,
            $plainPublicKey,
            $plainPassphrase
        );
    }

}
