<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Filters\AuthFilter;
use App\Models\UserModel;
use App\Services\AuthenticationService;
use App\Services\DavAuthenticationService;
use App\Services\ExternalAuth\ExternalAccountService;
use App\Services\ExternalAuth\ExternalAuthConfiguration;
use App\Services\ExternalAuth\ExternalAuthenticationService;
use App\Services\ExternalAuth\ExternalIdentity;
use App\Services\ExternalAuth\ExternalSessionPolicy;
use App\Services\ExternalAuth\OperatorFile;
use App\Services\RememberMeService;
use App\Services\StepUpAuthenticationService;
use App\Services\WebDavCredentialService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

final class ExternalAuthenticationTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string $configurationFile;
    private string|false $previousConfiguration;
    private array $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        session()->destroy();
        $model = new UserModel();
        $model->saveRole('reader', ['read']);
        $model->saveGroup('editors', ['user']);
        $this->configuration = ['extension_path' => ROOTPATH . 'tests/Support/ExternalAuth', 'providers' => ['website' => [
            'type' => 'custom', 'class' => 'FixtureProvider', 'issuer' => 'https://website.test',
            'base_role' => 'reader', 'group_map' => ['member' => [], 'editor' => ['editors'], '42' => []],
        ]]];
        $this->configurationFile = tempnam(sys_get_temp_dir(), 'extplorer-auth-');
        $this->previousConfiguration = getenv('EXTPLORER_AUTH_CONFIG_FILE');
        putenv('EXTPLORER_AUTH_CONFIG_FILE=' . $this->configurationFile);
        $this->saveConfiguration();
        \ExtplorerAuth\FixtureProvider::$identity = $this->identity();
        \ExtplorerAuth\FixtureProvider::$reauthentication = true;
    }

    protected function tearDown(): void
    {
        session()->destroy();
        unlink($this->configurationFile);
        putenv($this->previousConfiguration === false ? 'EXTPLORER_AUTH_CONFIG_FILE' : 'EXTPLORER_AUTH_CONFIG_FILE=' . $this->previousConfiguration);
        $this->sandbox->close();
        parent::tearDown();
    }

    private function saveConfiguration(): void
    {
        file_put_contents($this->configurationFile, json_encode($this->configuration, JSON_THROW_ON_ERROR));
        (new ExternalAuthConfiguration())->createProvider('website');
    }

    private function identity(string $subject = '123', array $groups = ['member'], ?int $authenticatedAt = null): ExternalIdentity
    {
        return new ExternalIdentity('https://website.test', $subject, 'Alice', $groups, $authenticatedAt ?? time());
    }

    private function login(): array
    {
        $service = new ExternalAuthenticationService();
        $transaction = $service->create('website', '/');
        $service->begin('website', $transaction['state'], Services::request());
        $service->complete('website', $transaction['state'], Services::request());
        return (new UserModel())->getUser((string)session('username'));
    }

    public function testProvisioningUsesAnImmutableIdentityAndNeverLinksByDisplayName(): void
    {
        $model = new UserModel();
        $model->addUser('Alice', 'Correct-Password-123!', 'admin');
        $provider = (new ExternalAuthConfiguration())->provider('website');
        $accounts = new ExternalAccountService();
        $first = $accounts->synchronize('website', $provider, $this->identity());
        $again = $accounts->synchronize('website', $provider, $this->identity());
        $other = $accounts->synchronize('website', $provider, $this->identity('456'));
        $this->assertSame($first['username'], $again['username']);
        $this->assertNotSame('Alice', $first['username']);
        $this->assertNotSame($first['username'], $other['username']);
        $this->assertNull($first['password_hash']);
        $this->assertSame('/external/' . $first['username'], $first['home_dir']);
        $this->assertSame(['read'], $model->getPermissions($first['username']));
        $this->assertSame('admin', $model->getUser('Alice')['role']);
        $numeric = $accounts->synchronize('website', $provider, $this->identity('numeric-group', ['42']));
        $this->assertSame(['read'], $model->getPermissions($numeric['username']));
    }

    public function testAdmissionAndMappingAreFailClosedAndRightsChangesInvalidateOldSessions(): void
    {
        $user = $this->login();
        $this->assertTrue(ExternalSessionPolicy::isValid($user));
        $provider = (new ExternalAuthConfiguration())->provider('website');
        $changed = (new ExternalAccountService())->synchronize('website', $provider, $this->identity('123', ['member', 'editor']));
        $this->assertSame($user['auth_version'] + 1, $changed['auth_version']);
        $request = Services::request();
        $response = (new AuthFilter())->before($request);
        $this->assertNotNull($response);
        $this->assertFalse((bool)session('isLoggedIn'));
        $this->expectException(\RuntimeException::class);
        (new ExternalAccountService())->synchronize('website', $provider, $this->identity('new', ['unknown']));
    }

    public function testDisabledAccountIsNeverReenabledByProvisioning(): void
    {
        $user = $this->login();
        (new UserModel())->updateUser($user['username'], ['disabled' => true]);
        $this->expectException(\RuntimeException::class);
        (new ExternalAccountService())->synchronize('website', (new ExternalAuthConfiguration())->provider('website'), $this->identity());
    }

    public function testExternalCredentialsCannotBecomeLocalPasswordRememberedOrDavLogins(): void
    {
        $user = $this->login();
        $this->assertFalse((new AuthenticationService())->startLocalSession($user));
        $this->assertNull((new UserModel())->verifyUser($user['username'], 'anything'));
        $this->assertNull((new DavAuthenticationService())->authenticate($user['username'], 'anything'));
        (new RememberMeService())->remember($user['username']);
        $this->assertSame([], \App\Services\AtomicFileStore::read(config('Storage')->state . '/remember_tokens.php'));
        $this->expectException(\RuntimeException::class);
        (new WebDavCredentialService())->create($user['username'], 'device');
    }

    public function testManagedPasswordAndGroupMutationsAreDenied(): void
    {
        $user = $this->login();
        $this->expectException(\RuntimeException::class);
        (new UserModel())->updateUser($user['username'], ['password' => 'Correct-Password-123!']);
    }

    public function testExternalSessionsExpireAbsolutelyAndRejectChangedProviderConfiguration(): void
    {
        $user = $this->login();
        $external = session('external_auth');
        $external['expires_at'] = time() - 1;
        session()->set('external_auth', $external);
        $this->assertFalse(ExternalSessionPolicy::isValid($user));
        $external['expires_at'] = time() + 3600;
        session()->set('external_auth', $external);
        $this->configuration['providers']['website']['base_role'] = 'user';
        $this->saveConfiguration();
        $this->assertFalse(ExternalSessionPolicy::isValid($user));
    }

    public function testCallbackIsOneTimeAndTheExchangeProofIsRequiredForStepUp(): void
    {
        $this->login();
        $service = new ExternalAuthenticationService();
        $transaction = $service->create('website', '/', true);
        $service->begin('website', $transaction['state'], Services::request());
        $this->assertNull($service->exchange($transaction['state'], $transaction['exchange_secret']));
        $service->complete('website', $transaction['state'], Services::request());
        try {
            $service->complete('website', $transaction['state'], Services::request());
            $this->fail('Replayed callback must fail.');
        } catch (\RuntimeException) {}
        try {
            $service->exchange($transaction['state'], str_repeat('0', 64));
            $this->fail('Exchange without memory-only proof must fail.');
        } catch (\RuntimeException) {}
        $token = $service->exchange($transaction['state'], $transaction['exchange_secret']);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $token);
        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', $token);
        $this->assertTrue((new StepUpAuthenticationService())->consume($request, 'mount.create'));
        $request->setHeader('X-Extplorer-Step-Up', '');
        $this->assertFalse((new StepUpAuthenticationService())->consume($request, 'mount.create'));
        $this->expectException(\RuntimeException::class);
        $service->exchange($transaction['state'], $transaction['exchange_secret']);
    }

    public function testStepUpRejectsAnotherIdentityAndStaleAuthentication(): void
    {
        $this->login();
        $service = new ExternalAuthenticationService();
        foreach ([$this->identity('another'), $this->identity('123', ['member'], time() - 120)] as $identity) {
            $transaction = $service->create('website', '/', true);
            $service->begin('website', $transaction['state'], Services::request());
            \ExtplorerAuth\FixtureProvider::$identity = $identity;
            try {
                $service->complete('website', $transaction['state'], Services::request());
                $this->fail('Fresh proof for the current identity is required.');
            } catch (\RuntimeException) {}
            $service->cancel($transaction['state']);
        }
        $this->assertNull(session('step_up_grant'));
    }

    public function testExpiredTransactionsAndUnsupportedStepUpAreRejected(): void
    {
        $this->login();
        $service = new ExternalAuthenticationService();
        $transaction = $service->create('website', '/', true);
        $transactions = session('external_auth_transactions');
        $transactions[$transaction['state']]['expires_at'] = time() - 1;
        session()->set('external_auth_transactions', $transactions);
        try {
            $service->begin('website', $transaction['state'], Services::request());
            $this->fail('Expired transaction must fail.');
        } catch (\RuntimeException) {}
        \ExtplorerAuth\FixtureProvider::$reauthentication = false;
        $this->expectException(\RuntimeException::class);
        $service->create('website', '/', true);
    }

    public function testOperatorConfigurationCannotBeLoadedFromManagedData(): void
    {
        $file = $this->sandbox->root . '/files/evil.json';
        file_put_contents($file, '{}');
        $this->expectException(\RuntimeException::class);
        OperatorFile::path($file);
    }

    public function testConcurrentFirstLoginsCreateExactlyOneAccount(): void
    {
        if (!function_exists('pcntl_fork')) $this->markTestSkipped('pcntl is required.');
        $provider = (new ExternalAuthConfiguration())->provider('website');
        $children = [];
        for ($index = 0; $index < 3; $index++) {
            $pid = pcntl_fork();
            $this->assertNotSame(-1, $pid);
            if ($pid === 0) {
                try {
                    $account = (new ExternalAccountService())->synchronize('website', $provider, $this->identity());
                    file_put_contents($this->sandbox->root . '/result-' . getmypid(), $account['username']);
                    exit(0);
                } catch (\Throwable) { exit(1); }
            }
            $children[] = $pid;
        }
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }
        $results = array_map('file_get_contents', glob($this->sandbox->root . '/result-*'));
        $this->assertCount(1, array_unique($results));
        $this->assertCount(1, (new UserModel())->getUsers());
    }

    public function testProvisioningAndRightsSynchronizationUseTheSqlBackend(): void
    {
        $this->sandbox->close();
        $this->sandbox = new StorageSandbox('sqlite');
        $model = new UserModel();
        $model->saveRole('reader', ['read']);
        $model->saveGroup('editors', ['user']);
        $settings = (new ExternalAuthConfiguration())->provider('website');
        $accounts = new ExternalAccountService();
        $first = $accounts->synchronize('website', $settings, $this->identity());
        $again = $accounts->synchronize('website', $settings, $this->identity());
        $this->assertSame($first['username'], $again['username']);
        $this->assertCount(1, $model->getUsers());
        $changed = $accounts->synchronize('website', $settings, $this->identity('123', ['editor']));
        $this->assertSame(2, $changed['auth_version']);
        $this->assertContains('write', $model->getPermissions($first['username']));
    }

    public function testAutomaticProviderPreservesLocalRecoveryAndExplicitLogout(): void
    {
        $this->configuration['default_provider'] = 'website';
        $this->saveConfiguration();
        $request = Services::request();
        $controller = new \App\Controllers\Login();
        $controller->initController($request, Services::response(), Services::logger());
        $request->setGlobal('get', []);
        $response = $controller->index();
        $this->assertStringContainsString('/login/external/website?', $response->getHeaderLine('Location'));
        foreach ([['local' => '1'], ['logged_out' => '1'], ['expired' => '1']] as $parameters) {
            $request->setGlobal('get', $parameters);
            $this->assertStringContainsString('id="login_username"', $controller->index());
        }
        $response = $controller->logout();
        $this->assertStringEndsWith('/login?logged_out=1', $response->getHeaderLine('Location'));
    }

    public function testPublicProviderRoutesAreThrottledBeforeProviderWork(): void
    {
        $original = Services::throttler();
        $throttler = $this->createMock(\CodeIgniter\Throttle\Throttler::class);
        $throttler->expects($this->exactly(2))->method('check')->willReturn(false);
        Services::injectMock('throttler', $throttler);
        $controller = new \App\Controllers\ExternalLogin();
        $controller->initController(Services::request(), Services::response(), Services::logger());
        try {
            foreach (['begin', 'callback'] as $method) {
                $response = $controller->$method('unknown-provider');
                $this->assertSame(429, $response->getStatusCode());
                $this->assertSame('60', $response->getHeaderLine('Retry-After'));
            }
            $this->assertNull(session('external_auth_transactions'));
        } finally {
            Services::injectMock('throttler', $original);
        }
    }
}
