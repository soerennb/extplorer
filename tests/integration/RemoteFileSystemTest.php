<?php

namespace Tests\Integration;

use App\Models\UserModel;
use App\Services\AtomicFileStore;
use App\Services\FileUploadService;
use App\Services\MountService;
use App\Services\UploadQuarantineService;
use App\Services\UploadSessionService;
use App\Services\UploadTargetResolver;
use App\Services\VFS\FileCapabilities;
use App\Services\VFS\VfsFactory;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use phpseclib4\Crypt\PublicKeyLoader;
use phpseclib4\Exception\FileSystemException;
use phpseclib4\Net\SFTP\StatusCode;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

/** Runs against disposable real FTP/FTPS/SFTP servers; never a user's endpoint. */
final class RemoteFileSystemTest extends CIUnitTestCase
{
    private ?StorageSandbox $sandbox = null;
    private array $environment = [];
    private array $connection;
    private ?\App\Services\VFS\IFileSystem $fs = null;
    private string $directory;
    private string $encryptionKey;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('EXTPLORER_RUN_REMOTE_TEST') !== '1') $this->markTestSkipped('Run tests/integration/remote/run.sh for real remote servers.');
        helper('auth');
        $this->sandbox = new StorageSandbox();
        $this->encryptionKey = config('Encryption')->key;
        config('Encryption')->key = str_repeat('integration-fixture-key-', 2);
        Services::resetSingle('encrypter');
        foreach (['EXTPLORER_UPLOAD_SCAN_MODE', 'EXTPLORER_REMOTE_SECURITY_MODE', 'EXTPLORER_REMOTE_LOGIN_ENABLED', 'EXTPLORER_FTPS_CA_FILE', 'EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST'] as $key) $this->environment[$key] = getenv($key);
        putenv('EXTPLORER_FTPS_CA_FILE=/workspace/build/remote-fixture/ca.pem');
        putenv('EXTPLORER_REMOTE_LOGIN_ENABLED=1');
        putenv('EXTPLORER_UPLOAD_SCAN_MODE=off');
        (new UserModel())->saveUsers([['username' => 'alice', 'role' => 'admin', 'groups' => [], 'auth_version' => 1, 'home_dir' => '/']]);
        session()->set(['username' => 'alice', 'permissions' => ['*'], 'allowed_extensions' => '', 'blocked_extensions' => '']);
    }

    protected function tearDown(): void
    {
        if ($this->fs !== null) {
            try { $this->fs->delete($this->directory); } catch (\Throwable) {}
        }
        foreach ($this->environment as $key => $value) putenv($value === false ? $key : $key . '=' . $value);
        if ($this->sandbox !== null) {
            config('Encryption')->key = $this->encryptionKey;
            Services::resetSingle('encrypter');
            session()->destroy();
            $this->sandbox->close();
        }
        parent::tearDown();
    }

    public static function protocols(): iterable
    {
        yield 'FTP compatibility' => ['ftp'];
        yield 'FTPS explicit compatibility' => ['ftps'];
        yield 'SFTP strict' => ['sftp'];
    }

    #[DataProvider('protocols')]
    public function testSingleChunkedEditAndQuarantineWrites(string $protocol): void
    {
        $this->connect($protocol);
        $capabilities = FileCapabilities::forPath($this->fs, $this->directory);
        $this->assertTrue($capabilities['upload']);
        $this->assertTrue($capabilities['edit']);
        $this->assertFalse($capabilities['trash']);
        $this->assertFalse($capabilities['versions']);
        $uploads = new FileUploadService();
        $source = $this->source('single payload');
        $single = $uploads->upload($source, $this->fs, 'alice', $this->connection, $this->directory, 'single.txt', '', 'replace');
        $this->assertSame('success', $single['status']);
        $this->assertSame('single payload', $this->fs->readFile($this->directory . '/single.txt'));
        $this->assertTrue($this->fs->writeFile($this->directory . '/single.txt', 'edited payload'));
        $this->assertSame('edited payload', $this->fs->readFile($this->directory . '/single.txt'));

        $sessions = new UploadSessionService();
        $payload = str_repeat('binary' . "\0", 20000);
        $created = $sessions->create('alice', $this->directory, '', 'large.txt', strlen($payload), 65536);
        $target = (new UploadTargetResolver())->capture('alice', $this->connection, $this->directory . '/large.txt');
        $sessions->bindTarget($created['id'], $target);
        for ($index = 0; $index < $created['total_chunks']; $index++) {
            $chunk = substr($payload, $index * 65536, 65536);
            $sessions->storeChunkFromPath($created['id'], $index, $this->source($chunk), strlen($chunk));
        }
        $result = $sessions->finalize($created['id'], static fn(array $manifest, string $assembled): array => (new FileUploadService())->accept($assembled, $target, 'replace', 'large.txt'));
        $this->assertSame('success', $result['status']);
        $this->assertSame($payload, $this->fs->readFile($this->directory . '/large.txt'));

        $this->assertTrue($this->fs->copy($this->directory . '/single.txt', $this->directory . '/copy.txt'));
        $this->assertSame('edited payload', $this->fs->readFile($this->directory . '/copy.txt'));
        $this->assertTrue($this->fs->delete($this->directory . '/copy.txt'));

        putenv('EXTPLORER_UPLOAD_SCAN_MODE=external');
        $pending = $uploads->upload($this->source('scan payload'), $this->fs, 'alice', $this->connection, $this->directory, 'scanned.txt', '', 'replace');
        $this->assertSame('pending_scan', $pending['status']);
        $this->assertNull($this->fs->getMetadata($this->directory . '/scanned.txt'));
        $quarantine = new UploadQuarantineService();
        $manifest = $quarantine->get($pending['quarantine_id']);
        $this->assertSame('remote', $manifest['target']['kind']);
        $this->assertStringNotContainsString('fixture-password', json_encode($manifest));
        $sealed = AtomicFileStore::read(config('Storage')->state . '/remote-upload-connections.php');
        $this->assertStringNotContainsString('fixture-password', json_encode($sealed));
        $this->assertSame('success', $quarantine->markResult($pending['quarantine_id'], 'clean')['activation_status']);
        $this->assertSame('scan payload', $this->fs->readFile($this->directory . '/scanned.txt'));
        $this->assertSame([], AtomicFileStore::read(config('Storage')->state . '/remote-upload-connections.php'));

        $created = $sessions->create('alice', $this->directory, '', 'chunk-scanned.txt', strlen($payload), 65536);
        $target = (new UploadTargetResolver())->capture('alice', $this->connection, $this->directory . '/chunk-scanned.txt');
        $sessions->bindTarget($created['id'], $target);
        for ($index = 0; $index < $created['total_chunks']; $index++) {
            $chunk = substr($payload, $index * 65536, 65536);
            $sessions->storeChunkFromPath($created['id'], $index, $this->source($chunk), strlen($chunk));
        }
        $pending = $sessions->finalize($created['id'], static fn(array $manifest, string $assembled): array => (new FileUploadService())->accept($assembled, $target, 'replace', 'chunk-scanned.txt'));
        $this->assertSame('pending_scan', $pending['status']);
        $this->assertNull($this->fs->getMetadata($this->directory . '/chunk-scanned.txt'));
        $this->assertNotEmpty(AtomicFileStore::read(config('Storage')->state . '/remote-upload-connections.php'));
        $quarantine->markResult($pending['quarantine_id'], 'clean');
        $this->assertSame($payload, $this->fs->readFile($this->directory . '/chunk-scanned.txt'));
        $this->assertSame([], AtomicFileStore::read(config('Storage')->state . '/remote-upload-connections.php'));
        $this->assertSame([], array_filter($this->fs->listDirectory($this->directory), static fn(array $entry): bool => str_starts_with($entry['name'], '.extplorer-write-')));
    }

    public function testMountReconfigurationAndEndpointRevocationRetainPendingPayload(): void
    {
        $this->connect('sftp');
        $mounts = new MountService();
        $configuration = $this->connection;
        unset($configuration['mode'], $configuration['direct_login']);
        $id = $mounts->addMount('alice', 'Fixture', 'sftp', $configuration);
        $resolver = new UploadTargetResolver();
        $target = $resolver->capture('alice', ['mode' => 'local'], 'Fixture/' . $this->directory . '/delayed.txt');
        $quarantine = new UploadQuarantineService();
        $pending = $quarantine->stage($this->source('delayed payload'), $target, ['owner' => 'alice']);
        $changed = $configuration;
        $changed['root'] = '/home/fixture';
        $mounts->updateMount($id, 'alice', 'Fixture', 'sftp', $changed);
        try {
            $quarantine->markResult($pending['id'], 'clean');
            $this->fail('A changed mount must not receive a pending upload.');
        } catch (\RuntimeException $error) { $this->assertStringContainsString('namespace changed', $error->getMessage()); }
        $this->assertFileExists($pending['payload_path']);
        $this->assertNull($this->fs->getMetadata($this->directory . '/delayed.txt'));
        $mounts->updateMount($id, 'alice', 'Fixture', 'sftp', $configuration);
        putenv('EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST=sftp://revoked.example.test:22');
        try {
            $quarantine->markResult($pending['id'], 'clean');
            $this->fail('A revoked endpoint must not receive a pending upload.');
        } catch (\RuntimeException $error) { $this->assertStringContainsString('not allowlisted', $error->getMessage()); }
        $this->assertFileExists($pending['payload_path']);
        putenv('EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST=' . $this->environment['EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST']);
        $quarantine->markResult($pending['id'], 'clean');
        $this->assertSame('delayed payload', $this->fs->readFile($this->directory . '/delayed.txt'));
    }

    public static function keyProtection(): iterable
    {
        yield 'encrypted' => [true];
        yield 'unencrypted' => [false];
    }

    #[DataProvider('keyProtection')]
    public function testSftpAuthenticatesWithKeyAndRejectsWrongHostPin(bool $encrypted): void
    {
        $this->connect('sftp');
        $connection = $this->connection;
        $connection['pass'] = 'intentionally-wrong-password';
        $connection['private_key'] = file_get_contents('/workspace/build/remote-fixture/client-key');
        $connection['public_key'] = file_get_contents('/workspace/build/remote-fixture/client-key.pub');
        $connection['private_key_passphrase'] = 'fixture-key-passphrase';
        if (!$encrypted) {
            $connection['private_key'] = PublicKeyLoader::loadPrivateKey($connection['private_key'], $connection['private_key_passphrase'])
                ->withPassword(null)->toString('PKCS8');
            $connection['private_key_passphrase'] = '';
        }
        $keyFs = VfsFactory::createAdapter('sftp', $connection);
        $this->assertTrue($keyFs->writeFile($this->directory . '/key-auth.txt', 'key authentication'));
        $this->assertSame('key authentication', $keyFs->readFile($this->directory . '/key-auth.txt'));
        $connection['host_key_fingerprint'] = 'SHA256:' . base64_encode(str_repeat('x', 32));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fingerprint');
        VfsFactory::createAdapter('sftp', $connection);
    }

    public function testSftpRejectsIncorrectPassword(): void
    {
        $this->connect('sftp');
        $connection = $this->connection;
        $connection['pass'] = 'intentionally-wrong-password';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SSH authentication failed');
        VfsFactory::createAdapter('sftp', $connection);
    }

    public function testSftpMutationAndMissingFileContracts(): void
    {
        $this->connect('sftp');
        $nested = $this->directory . '/nested';
        $this->assertNull($this->fs->getMetadata($nested . '/missing'));
        $this->assertTrue($this->fs->createDirectory($nested));
        $this->assertTrue($this->fs->writeFile($nested . '/old', 'payload'));
        $this->assertTrue($this->fs->rename($nested . '/old', $nested . '/new'));
        $this->assertNull($this->fs->getMetadata($nested . '/old'));
        $this->assertTrue($this->fs->chmod($nested . '/new', 0600));
        $this->assertSame('0600', $this->fs->getMetadata($nested . '/new')['perms']);
        $this->assertSame('payload', $this->fs->readFile($nested . '/new'));

        $this->assertTrue($this->fs->chmod($nested, 0000));
        try {
            try {
                $this->fs->getMetadata($nested . '/new');
                $this->fail('Permission denied must not be treated as a missing file.');
            } catch (FileSystemException $error) {
                $this->assertSame(StatusCode::PERMISSION_DENIED, $error->getCode());
            }
        } finally { $this->fs->chmod($nested, 0755); }

        $this->assertTrue($this->fs->delete($nested));
        $this->assertNull($this->fs->getMetadata($nested));
        $this->assertTrue($this->fs->delete($nested));
    }

    private function connect(string $protocol): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=' . ($protocol === 'sftp' ? 'strict' : 'compat'));
        $this->connection = ['mode' => $protocol, 'host' => 'remote.fixture', 'port' => $protocol === 'sftp' ? 22 : 21,
            'user' => 'fixture', 'pass' => 'fixture-password', 'root' => $protocol === 'sftp' ? '/home/fixture/data' : '/', 'direct_login' => true,
            'host_key_fingerprint' => trim(file_get_contents('/workspace/build/remote-fixture/fingerprint'))];
        $this->fs = VfsFactory::createAdapter($protocol, $this->connection);
        $this->directory = 'audit-' . bin2hex(random_bytes(8));
        $this->assertTrue($this->fs->createDirectory($this->directory));
    }

    private function source(string $payload): string
    {
        $path = config('Storage')->runtime . '/incoming-' . bin2hex(random_bytes(6));
        file_put_contents($path, $payload);
        return $path;
    }
}
