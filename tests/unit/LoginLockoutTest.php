<?php

namespace Tests\Unit;

use App\Filters\AuthFilter;
use App\Models\UserModel;
use App\Services\AuthenticationService;
use App\Services\DavAuthenticationService;
use App\Services\DeviceTrustService;
use App\Services\RememberMeService;
use App\Services\SettingsService;
use App\Services\ShareService;
use App\Services\WebDavCredentialService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

/**
 * A password-guessing lock protects the password. Anonymous failures must
 * neither lock an account indefinitely nor end the owner's other access.
 */
final class LoginLockoutTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([[
            'username' => 'alice',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'user',
            'home_dir' => '/alice',
            'auth_version' => 1,
        ]]);
        (new SettingsService())->saveSettings(['session_idle_timeout_minutes' => 0]);
        unset($_COOKIE[DeviceTrustService::COOKIE_NAME], $_COOKIE[RememberMeService::COOKIE_NAME]);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE[DeviceTrustService::COOKIE_NAME], $_COOKIE[RememberMeService::COOKIE_NAME]);
        session()->destroy();
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testExpiredLockStartsANewFailureWindow(): void
    {
        $model = new UserModel();
        $this->lockByAnonymousFailures();
        $model->updateUser('alice', ['locked_until' => time() - 1]);

        $this->assertNull($model->verifyUser('alice', 'wrong-password'));

        $this->assertLessThanOrEqual(time(), (int)$model->getUser('alice')['locked_until']);
        $this->assertNotNull($model->verifyUser('alice', 'correct-password'));
    }

    public function testPasswordLockDoesNotEndExistingAccess(): void
    {
        mkdir(config('Storage')->fileManagerRoot . '/alice/public', 0700, true);
        $share = (new ShareService())->createShare('Home/public', 'alice');
        session()->set(['isLoggedIn' => true, 'username' => 'alice', 'auth_version' => 1, 'connection' => ['mode' => 'local']]);

        $this->lockByAnonymousFailures();

        $this->assertNull((new AuthFilter())->before(Services::request()), 'An authenticated session was terminated.');
        $this->assertTrue(session('isLoggedIn'));
        $this->assertNotSame('', (new ShareService())->resolveSharePaths($share)[1]);
    }

    public function testRememberedLoginSurvivesPasswordLock(): void
    {
        $response = Services::response();
        (new RememberMeService())->remember('alice', $response);
        $_COOKIE[RememberMeService::COOKIE_NAME] = $response->getCookie(RememberMeService::COOKIE_NAME)->getValue();
        $this->lockByAnonymousFailures();

        $this->assertTrue((new RememberMeService())->restore(Services::request(), Services::response()));
        $this->assertSame('alice', session('username'));
    }

    public function testWebDavAppPasswordWorksDuringPasswordLock(): void
    {
        $secret = (new WebDavCredentialService())->create('alice', 'Laptop')['secret'];
        $this->lockByAnonymousFailures();

        $dav = new DavAuthenticationService();
        $this->assertSame('alice', $dav->authenticate('alice', $secret)['username'] ?? null);
        $this->assertNull($dav->authenticate('alice', 'correct-password'));
    }

    public function testTrustedDeviceSignsInDuringAccountLock(): void
    {
        $this->trustThisBrowser();
        $this->lockByAnonymousFailures();

        $auth = new AuthenticationService();
        $this->assertSame('alice', $auth->verifyPassword(Services::request(), 'alice', 'correct-password')['username'] ?? null);

        unset($_COOKIE[DeviceTrustService::COOKIE_NAME]);
        $this->lockByAnonymousFailures();
        $this->assertNull($auth->verifyPassword(Services::request(), 'alice', 'correct-password'));
    }

    public function testTrustedDeviceHasItsOwnFailureLimit(): void
    {
        $this->trustThisBrowser();
        $auth = new AuthenticationService();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->assertNull($auth->verifyPassword(Services::request(), 'alice', 'wrong-password'));
        }

        $this->assertSame(0, (int)(new UserModel())->getUser('alice')['locked_until']);
        $this->assertSame(0, (int)(new UserModel())->getUser('alice')['failed_login_count']);
        // The locked device loses its exemption: further failures count against the account.
        $this->assertFalse((new DeviceTrustService())->isTrusted(Services::request(), 'alice'));
        $this->assertNull($auth->verifyPassword(Services::request(), 'alice', 'wrong-password'));
        $this->assertSame(1, (int)(new UserModel())->getUser('alice')['failed_login_count']);
    }

    public function testDeviceTrustIsBoundToItsAccount(): void
    {
        (new UserModel())->saveUsers(array_merge((new UserModel())->getUsers(), [[
            'username' => 'bob',
            'password_hash' => password_hash('bob-password', PASSWORD_DEFAULT),
            'role' => 'user',
            'home_dir' => '/bob',
            'auth_version' => 1,
        ]]));
        $this->trustThisBrowser('bob');

        $this->assertTrue((new DeviceTrustService())->isTrusted(Services::request(), 'bob'));
        $this->assertFalse((new DeviceTrustService())->isTrusted(Services::request(), 'alice'));
        $_COOKIE[DeviceTrustService::COOKIE_NAME] = 'forged:' . str_repeat('a', 64);
        $this->assertFalse((new DeviceTrustService())->isTrusted(Services::request(), 'bob'));
    }

    private function lockByAnonymousFailures(): void
    {
        $model = new UserModel();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $model->verifyUser('alice', 'wrong-password');
        }
        $this->assertGreaterThan(time(), (int)$model->getUser('alice')['locked_until']);
    }

    private function trustThisBrowser(string $username = 'alice'): void
    {
        $response = Services::response();
        (new DeviceTrustService())->trust($username, $response);
        $_COOKIE[DeviceTrustService::COOKIE_NAME] = $response->getCookie(DeviceTrustService::COOKIE_NAME)->getValue();
    }
}
