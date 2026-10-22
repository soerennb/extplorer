<?php

namespace Tests\Unit;

use App\Controllers\ProfileController;
use App\Filters\AuthFilter;
use App\Models\UserModel;
use App\Services\SettingsService;
use App\Services\SessionAccount;
use App\Services\StepUpAuthenticationService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

/**
 * A direct FTP/SFTP login authenticates a remote account, not the local
 * account that happens to have the same name.
 */
final class RemoteSessionIsolationTest extends CIUnitTestCase
{
    private const ENVIRONMENT = [
        'EXTPLORER_REMOTE_LOGIN_ENABLED' => 'true',
        'EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST' => 'sftp://93.184.216.34:22',
        'EXTPLORER_REMOTE_SECURITY_MODE' => 'strict',
        'EXTPLORER_REMOTE_ALLOW_PRIVATE_TARGETS' => null,
    ];

    private StorageSandbox $sandbox;
    /** @var array<string, string|null> */
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::ENVIRONMENT as $key => $value) {
            $current = getenv($key);
            $this->environment[$key] = $current === false ? null : $current;
            putenv($value === null ? $key : "{$key}={$value}");
        }
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([[
            'username' => 'alice',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'user',
            'home_dir' => '/alice',
            'auth_version' => 1,
        ]]);
        (new SettingsService())->saveSettings(['session_idle_timeout_minutes' => 0]);
        session()->set([
            'isLoggedIn' => true,
            'username' => 'alice',
            'auth_version' => 0,
            'permissions' => ['read', 'write', 'upload', 'delete', 'chmod'],
            'connection' => [
                'mode' => 'sftp',
                'host' => '93.184.216.34',
                'port' => 22,
                'user' => 'alice',
                'host_key_fingerprint' => str_repeat('ab', 32),
                'direct_login' => true,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        session()->destroy();
        $this->sandbox->close();
        foreach ($this->environment as $key => $value) {
            putenv($value === null ? $key : "{$key}={$value}");
        }
        parent::tearDown();
    }

    /** @return iterable<string, array{string}> */
    public static function localAccountRoutes(): iterable
    {
        foreach ([
            'api/share/list', 'api/share/delete', 'api/share/create', 'api/share/policy',
            'api/trash/list', 'api/trash/empty', 'api/trash/delete', 'api/trash/restore',
            'api/versions/list', 'api/versions/restore',
            'api/transfer/history', 'api/transfer/send', 'api/transfer/abc',
            'api/mounts', 'api/mounts/mnt_x', 'api/users', 'api/settings', 'api/logs',
            'api/security/step-up', 'api/profile/password', 'api/profile/2fa/setup',
            'api/profile/2fa/enable', 'api/profile/2fa/disable', 'api/profile/webdav-credentials',
            'admin', 'api/unknown-future-endpoint',
        ] as $route) {
            yield $route => [$route];
        }
    }

    #[DataProvider('localAccountRoutes')]
    public function testRemoteSessionCannotReachLocalAccountRoutes(string $route): void
    {
        $response = (new AuthFilter())->before($this->request($route));

        $this->assertNotNull($response, $route);
        $this->assertSame(403, $response->getStatusCode(), $route);
        $this->assertSame('remote_session_unsupported', json_decode($response->getBody(), true)['code'] ?? null);
    }

    public function testRemoteSessionKeepsRemoteFileOperations(): void
    {
        foreach (['', 'logout', 'api/ls', 'api/content', 'api/save', 'api/mkdir', 'api/mv', 'api/cp',
            'api/rm', 'api/upload', 'api/upload/session', 'api/upload/session/abc/chunk/0',
            'api/download', 'api/search', 'api/dirsize', 'api/archive', 'api/extract', 'api/chmod',
            'api/profile/details'] as $route) {
            $this->assertNull((new AuthFilter())->before($this->request($route)), $route);
        }
    }

    public function testRemoteSessionCannotEnrollTwoFactorOnLocalAccount(): void
    {
        $controller = new ProfileController();
        $controller->initController(Services::request(), Services::response(), Services::logger());

        $this->assertSame(403, $controller->setup2fa()->getStatusCode());
        session()->set('pending_2fa', ['secret' => 'JBSWY3DPEHPK3PXP', 'expires_at' => time() + 300]);
        Services::request()->setBody(json_encode(['code' => '123456']));
        $this->assertSame(403, $controller->enable2fa()->getStatusCode());
        $this->assertEmpty((new UserModel())->getUser('alice')['2fa_enabled'] ?? null);
        $this->assertNull((new UserModel())->get2faSecret('alice'));
    }

    public function testRemoteProfileDetailsDoNotDiscloseLocalAccount(): void
    {
        (new UserModel())->updateUser('alice', ['allowed_extensions' => 'jpg']);
        $controller = new ProfileController();
        $controller->initController(Services::request(), Services::response(), Services::logger());

        $payload = json_decode($controller->getDetails()->getBody(), true);

        $this->assertSame('sftp', $payload['connection_mode']);
        $this->assertArrayNotHasKey('home_dir', $payload);
        $this->assertArrayNotHasKey('role', $payload);
        $this->assertSame('', $payload['allowed_extensions']);
    }

    public function testRemoteSessionCannotObtainOrUseStepUpGrant(): void
    {
        $service = new StepUpAuthenticationService();
        try {
            $service->issue('mount.delete', 'correct-password');
            $this->fail('A remote session must not be re-authenticated as the local account.');
        } catch (\RuntimeException) {
            $this->assertNull(session('step_up_grant'));
        }

        session()->set('step_up_grant', [
            'token_hash' => hash('sha256', str_repeat('c', 64)),
            'username' => 'alice',
            'auth_version' => 1,
            'issued_at' => time(),
            'expires_at' => time() + 600,
        ]);
        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', str_repeat('c', 64));
        $this->assertFalse($service->consume($request, 'mount.delete'));
    }

    public function testRemoteSessionHasNoLocalAccountIdentity(): void
    {
        $this->assertFalse(SessionAccount::isLocal());
        $this->assertNull(SessionAccount::localUsername());

        session()->set('connection', ['mode' => 'local']);
        $this->assertTrue(SessionAccount::isLocal());
        $this->assertSame('alice', SessionAccount::localUsername());
    }

    private function request(string $route): IncomingRequest
    {
        $config = clone config('App');
        $config->baseURL = 'http://example.com/';
        $config->indexPage = '';
        return new IncomingRequest($config, new SiteURI($config, $route), null, new UserAgent());
    }
}
