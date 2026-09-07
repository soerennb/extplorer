<?php

namespace App\Services;

use App\Services\VFS\FtpAdapter;
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

        if (!file_exists($this->mountsFile)) {
            $this->saveMounts([]);
        }
    }

    private function getMounts(): array
    {
        if (!file_exists($this->mountsFile)) return [];
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
                $pass = $mount['config']['pass'] ?? null;
                if (is_string($pass) && $pass !== '' && !$this->isEncryptedSecret($pass)) {
                    $mounts[$id]['config']['pass'] = $this->encryptSecret($pass);
                }
            }
        });
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
        $existingEncryptedPass = (string)($existing['config']['pass'] ?? '');
        [$type, $config] = $this->validateAndNormalizeMount($type, $config, $existingEncryptedPass, true);
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

        $existingEncryptedPass = '';
        if ($id) {
            $existing = $this->getMountForUser($id, $username, true);
            $existingEncryptedPass = (string)($existing['config']['pass'] ?? '');
        }

        $name = $this->sanitizeMountName($name);
        $mounts = $this->getMounts();
        $this->assertMountNameAvailable($mounts, $username, $name, $id);
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

        if ($type === 'ssh2') {
            $type = 'sftp';
        }

        if (in_array($type, ['ftp', 'ftps', 'sftp'], true)) {
            $host = strtolower(trim((string)($config['host'] ?? '')));
            $user = trim((string)($config['user'] ?? ''));
            $passInput = (string)($config['pass'] ?? '');
            $portDefault = $type === 'sftp' ? 22 : ($type === 'ftps' ? 990 : 21);
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
            $config['__connect_host'] = $this->assertRemoteHostAllowed($host);
            (new RemoteSecurityPolicy())->assertProtocolAllowed($type, $config);
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
            if ($type === 'sftp') {
                $config['host_key_fingerprint'] = trim((string)($config['host_key_fingerprint'] ?? ''));
            } else {
                unset($config['host_key_fingerprint']);
            }
            $config['pass'] = $encryptedPass;
            $config['__plain_pass'] = $plainPass;
            return [$type, $config];
        }

        throw new \Exception("Unknown mount type.");
    }

    private function assertRemoteHostAllowed(string $host): string
    {
        $settingsService = new SettingsService();
        $allowlist = array_merge(
            config('App')->mountRemoteHostAllowlist ?? [],
            $settingsService->get('mount_remote_host_allowlist', [])
        );
        $allowlist = array_values(array_filter(array_map(
            static fn($entry) => is_string($entry) ? trim(strtolower($entry)) : '',
            $allowlist
        )));

        if (!empty($allowlist)) {
            if (!$this->hostMatchesAllowlist($host, $allowlist)) {
                throw new \Exception("Remote host is not allowlisted.");
            }
            $ips = $this->resolveHostIps($host);
            if ($ips === []) {
                throw new \Exception("Remote host could not be resolved.");
            }
            return $ips[0];
        }

        if ($this->isPrivateOrReservedHost($host)) {
            throw new \Exception("Remote host resolves to a private or reserved target.");
        }

        $ips = $this->resolveHostIps($host);
        if ($ips === []) {
            throw new \Exception("Remote host could not be resolved.");
        }
        return $ips[0];
    }

    private function hostMatchesAllowlist(string $host, array $allowlist): bool
    {
        $hostIps = $this->resolveHostIps($host);
        $isHostIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

        foreach ($allowlist as $entry) {
            if ($entry === '') {
                continue;
            }

            if ($entry === $host) {
                return true;
            }

            if (str_starts_with($entry, '*.')) {
                $suffix = substr($entry, 1);
                if (str_ends_with($host, $suffix)) {
                    return true;
                }
            }

            if (strpos($entry, '/') !== false) {
                foreach ($hostIps as $ip) {
                    if ($this->ipMatchesCidr($ip, $entry)) {
                        return true;
                    }
                }
                continue;
            }

            if ($isHostIp && $entry === $host) {
                return true;
            }

            if (filter_var($entry, FILTER_VALIDATE_IP) !== false) {
                if (in_array($entry, $hostIps, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isPrivateOrReservedHost(string $host): bool
    {
        $ips = $this->resolveHostIps($host);
        if (empty($ips)) {
            return true;
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<int, string>
     */
    private function resolveHostIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = [];

        $records = dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ips[] = $record['ip'];
                }
                if (isset($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if (empty($ips)) {
            $ipv4 = gethostbynamel($host);
            if (is_array($ipv4)) {
                foreach ($ipv4 as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        if ($ip === '::' || $ip === '::1') {
            return true;
        }

        return $this->ipv6MatchesPrefix($ip, 'fc00::', 7)   // Unique local
            || $this->ipv6MatchesPrefix($ip, 'fe80::', 10)  // Link-local
            || $this->ipv6MatchesPrefix($ip, 'ff00::', 8)   // Multicast
            || $this->ipv6MatchesPrefix($ip, '2001:db8::', 32); // Documentation
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $network = trim($parts[0]);
        $prefix = (int)trim($parts[1]);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && $prefix >= 0 && $prefix <= 32
        ) {
            $ipLong = ip2long($ip);
            $networkLong = ip2long($network);
            if ($ipLong === false || $networkLong === false) {
                return false;
            }

            $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
            return (($ipLong & $mask) === ($networkLong & $mask));
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && $prefix >= 0 && $prefix <= 128
        ) {
            return $this->ipv6MatchesPrefix($ip, $network, $prefix);
        }

        return false;
    }

    private function ipv6MatchesPrefix(string $ip, string $network, int $prefixLength): bool
    {
        $ipPacked = inet_pton($ip);
        $networkPacked = inet_pton($network);
        if ($ipPacked === false || $networkPacked === false) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0
            && substr($ipPacked, 0, $fullBytes) !== substr($networkPacked, 0, $fullBytes)
        ) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($ipPacked[$fullBytes]) & $mask) === (ord($networkPacked[$fullBytes]) & $mask);
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
        $connectHost = (string)($config['__connect_host'] ?? ($config['host'] ?? ''));
        unset($config['__plain_pass']);
        unset($config['__connect_host']);

        $host = (string)($config['host'] ?? '');
        $user = (string)($config['user'] ?? '');
        $port = (int)($config['port'] ?? ($type === 'sftp' ? 22 : 21));
        $root = (string)($config['root'] ?? '/');

        if ($type === 'ftp' || $type === 'ftps') {
            new FtpAdapter($connectHost, $user, $plainPass, $port, $root, $type === 'ftps');
            return;
        }

        new Ssh2Adapter($connectHost, $user, $plainPass, $port, $root, (string)($config['host_key_fingerprint'] ?? ''));
    }
}
