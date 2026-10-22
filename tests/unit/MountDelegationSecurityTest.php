<?php

namespace Tests\Unit;

use App\Controllers\ApiShareController;
use App\Models\UserModel;
use App\Services\AtomicFileStore;
use App\Services\AuthorizationState;
use App\Services\MountService;
use App\Services\ShareService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

/**
 * Saved remote credentials belong to one endpoint, and delegated
 * administrators may only act for accounts within their own authority.
 */
final class MountDelegationSecurityTest extends CIUnitTestCase
{
    private const FINGERPRINT = 'abababababababababababababababababababababababababababababababab';

    private StorageSandbox $sandbox;
    private string $originalKey;
    /** @var array<string, string|null> */
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();
        helper('auth');
        foreach (['EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST' => null, 'EXTPLORER_REMOTE_SECURITY_MODE' => 'strict'] as $key => $value) {
            $current = getenv($key);
            $this->environment[$key] = $current === false ? null : $current;
            putenv($value === null ? $key : "{$key}={$value}");
        }
        $this->sandbox = new StorageSandbox();
        $this->originalKey = (string)config('Encryption')->key;
        config('Encryption')->key = random_bytes(32);
        Services::resetSingle('encrypter');

        (new UserModel())->saveUsers([
            ['username' => 'root', 'role' => 'admin', 'home_dir' => '/', 'auth_version' => 1],
            ['username' => 'dana', 'role' => 'helpdesk', 'home_dir' => '/team', 'auth_version' => 1],
            ['username' => 'bob', 'role' => 'reader', 'home_dir' => '/team/bob', 'auth_version' => 1],
        ]);
        (new AuthorizationState())->replaceRoles([
            'admin' => ['*', 'admin_settings'],
            'user' => AuthorizationState::DEFAULT_ROLES['user'],
            'helpdesk' => ['read', 'admin_users'],
            'reader' => ['read'],
        ]);

        $service = new MountService();
        $secret = (fn(string $value): string => $this->encryptSecret($value))->call($service, 'stored-secret');
        AtomicFileStore::transaction(config('Storage')->state . '/mounts.php', function (array &$mounts) use ($secret): void {
            foreach (['mnt_root' => 'root', 'mnt_bob' => 'bob'] as $id => $owner) {
                $mounts[$id] = [
                    'id' => $id,
                    'user' => $owner,
                    'name' => 'Backup',
                    'type' => 'sftp',
                    'config' => [
                        'host' => '93.184.216.34', 'port' => 22, 'user' => 'backup', 'root' => '/',
                        'auth_method' => 'password', 'pass' => $secret,
                        'host_key_fingerprint' => self::FINGERPRINT,
                    ],
                ];
            }
        }, []);
        $this->actAs('root', ['*', 'admin_settings']);
    }

    protected function tearDown(): void
    {
        session()->destroy();
        config('Encryption')->key = $this->originalKey;
        Services::resetSingle('encrypter');
        $this->sandbox->close();
        foreach ($this->environment as $key => $value) {
            putenv($value === null ? $key : "{$key}={$value}");
        }
        parent::tearDown();
    }

    public function testChangingTheEndpointRequiresNewCredentials(): void
    {
        foreach ([
            ['sftp', ['host' => '93.184.216.35']],
            ['sftp', ['port' => 2222]],
            ['sftp', ['user' => 'other']],
            ['sftp', ['host_key_fingerprint' => str_repeat('cd', 32)]],
            ['ftp', []],
        ] as [$type, $change]) {
            try {
                (new MountService())->updateMount('mnt_root', 'root', 'Backup', $type, array_merge($this->endpoint(), $change));
                $this->fail('Stored credentials were reused for a different endpoint.');
            } catch (\Exception $exception) {
                $this->assertStringContainsString('credentials', $exception->getMessage(), json_encode([$type, $change]));
            }
        }
    }

    public function testConnectionTestCannotRetargetStoredCredentials(): void
    {
        $this->expectExceptionMessageMatches('/credentials/');
        (new MountService())->testMount('root', 'mnt_root', 'Backup', 'sftp', array_merge($this->endpoint(), ['host' => '93.184.216.35']));
    }

    public function testUnchangedEndpointStillReusesStoredCredentials(): void
    {
        // The endpoint is deliberately not allowlisted: reaching endpoint
        // authorization proves that the stored secret was accepted.
        $this->expectExceptionMessage('Remote endpoint is not allowlisted.');
        (new MountService())->updateMount('mnt_root', 'root', 'Backup', 'sftp', array_merge($this->endpoint(), ['root' => '/data']));
    }

    public function testDelegateCannotManageMountsOfStrongerAccounts(): void
    {
        $this->actAs('dana', ['read', 'admin_users']);
        $service = new MountService();
        foreach ([
            fn() => $service->getMountForUser('mnt_root', 'dana'),
            fn() => $service->getMountHealth('mnt_root', 'dana'),
            fn() => $service->updateMount('mnt_root', 'dana', 'Backup', 'sftp', $this->endpoint()),
            fn() => $service->testMount('dana', 'mnt_root', 'Backup', 'sftp', $this->endpoint()),
            fn() => $service->removeMount('mnt_root', 'dana'),
        ] as $index => $operation) {
            try {
                $operation();
                $this->fail("Delegated mount operation {$index} was allowed.");
            } catch (\Exception $exception) {
                $this->assertSame('Permission denied.', $exception->getMessage(), (string)$index);
            }
        }

        $service->recordMountHealth('mnt_root', 'dana', false, 'tampered');
        $this->assertSame('unknown', (new MountService())->getMountHealth('mnt_root', 'root')['status']);
    }

    public function testDelegateMayManageMountsWithinItsAuthority(): void
    {
        $this->actAs('dana', ['read', 'admin_users']);
        $this->assertSame('bob', (new MountService())->getMountForUser('mnt_bob', 'dana')['user']);
        $this->assertTrue((new MountService())->removeMount('mnt_bob', 'dana'));
    }

    public function testDelegateCannotDeleteSharesOfStrongerAccounts(): void
    {
        mkdir(config('Storage')->fileManagerRoot . '/report', 0700, true);
        $share = (new ShareService())->createShare('Home/report', 'root');
        $this->actAs('dana', ['read', 'admin_users']);

        $response = $this->shareController(['hash' => $share['hash']])->shareDelete();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull((new ShareService())->getShareRaw($share['hash']));
    }

    private function endpoint(): array
    {
        return ['host' => '93.184.216.34', 'port' => 22, 'user' => 'backup', 'root' => '/',
            'auth_method' => 'password', 'host_key_fingerprint' => self::FINGERPRINT];
    }

    private function actAs(string $username, array $permissions): void
    {
        session()->set(['isLoggedIn' => true, 'username' => $username, 'permissions' => $permissions,
            'auth_version' => 1, 'connection' => ['mode' => 'local']]);
    }

    private function shareController(array $body): ApiShareController
    {
        $request = Services::request();
        $request->setBody(json_encode($body));
        $controller = new ApiShareController();
        $controller->initController($request, Services::response(), Services::logger());
        return $controller;
    }
}
