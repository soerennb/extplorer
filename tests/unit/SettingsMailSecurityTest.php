<?php

namespace Tests\Unit;

use App\Controllers\SettingsController;
use App\Filters\CsrfFilter;
use App\Filters\CsrfHeaderFilter;
use App\Models\UserModel;
use App\Services\LogService;
use App\Services\SettingsService;
use App\Services\StepUpAuthenticationService;
use CodeIgniter\Email\Email;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class SettingsMailSecurityTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private array $cookies;
    private mixed $logPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cookies = service('superglobals')->getCookieArray();
        $this->sandbox = new StorageSandbox();
        $property = new \ReflectionProperty(LogService::class, 'filePath');
        $this->logPath = $property->getValue();
        $property->setValue(null, null);
        $username = 'settings-' . bin2hex(random_bytes(6));
        (new UserModel())->saveUsers([['username' => $username, 'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'user', 'auth_version' => 1, 'groups' => [], 'disabled' => false]]);
        session()->set(['username' => $username, 'permissions' => ['admin_settings'], 'isLoggedIn' => true, 'auth_version' => 1]);
        (new SettingsService())->saveSettings(['smtp_host' => 'saved.example.test', 'smtp_user' => 'saved-user', 'smtp_pass' => 'saved-secret']);
    }

    protected function tearDown(): void
    {
        session()->destroy();
        service('superglobals')->setCookieArray($this->cookies);
        foreach (['email', 'request', 'response', 'security', 'throttler'] as $name) Services::resetSingle($name);
        (new \ReflectionProperty(LogService::class, 'filePath'))->setValue(null, $this->logPath);
        $this->sandbox->close();
        parent::tearDown();
    }

    public static function deployments(): iterable
    {
        foreach (['http://example.com/', 'http://example.com/extplorer/public/'] as $base) {
            foreach (['', 'index.php'] as $index) yield [$base, $index];
        }
    }

    #[DataProvider('deployments')]
    public function testMailChecksRequireStepUpAndKeepTheCsrfToken(string $base, string $index): void
    {
        foreach (['testEmail', 'validateEmail'] as $method) {
            $response = $this->call($method, [], $base, $index);
            $this->assertSame(428, $response->getStatusCode());
            $this->assertSame('step_up_required', json_decode($response->getBody(), true)['error']);
        }
    }

    #[DataProvider('deployments')]
    public function testPathAndProbeOverridesAreRejectedBeforeAnyMailOrConnection(string $base, string $index): void
    {
        $email = $this->createMock(Email::class);
        $email->expects($this->never())->method('initialize');
        $email->expects($this->never())->method('send');
        Services::injectMock('email', $email);
        $this->grant();
        foreach ([['update', ['sendmail_path' => '/tmp/uploaded-program']],
            ['testEmail', ['email' => 'recipient@example.test', 'smtp_crypto' => 'TLS']],
            ['validateEmail', ['smtp_host' => 'attacker.example.test', 'smtp_pass' => '********']]] as [$method, $body]) {
            $response = $this->call($method, $body, $base, $index);
            $this->assertSame(422, $response->getStatusCode());
        }
        $this->assertSame('saved.example.test', (new SettingsService())->get('smtp_host'));
    }

    public function testUserAdministrationDoesNotImplicitlyGrantSettingsAccess(): void
    {
        session()->set('permissions', ['admin_users']);
        foreach (['index', 'update', 'testEmail', 'validateEmail'] as $method) {
            $this->assertSame(403, $this->call($method, [])->getStatusCode());
        }
    }

    public function testBothProbesShareOneAccountRateLimit(): void
    {
        $this->grant();
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(422, $this->call($i % 2 ? 'validateEmail' : 'testEmail', ['smtp_host' => 'override'])->getStatusCode());
        }
        $response = $this->call('validateEmail', []);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('email_probe_rate_limited', json_decode($response->getBody(), true)['error']);
    }

    public function testOnlySavedCredentialsReachTheTransport(): void
    {
        $this->grant();
        $email = $this->createMock(Email::class);
        $email->expects($this->once())->method('initialize')->with($this->callback(static fn(array $config): bool =>
            $config['SMTPHost'] === 'saved.example.test' && $config['SMTPPass'] === 'saved-secret' && $config['SMTPCrypto'] === 'tls'));
        $email->expects($this->once())->method('setTo')->with('recipient@example.test');
        $email->expects($this->once())->method('send')->willReturn(true);
        $email->expects($this->never())->method('printDebugger');
        Services::injectMock('email', $email);
        $response = $this->call('testEmail', ['email' => 'recipient@example.test']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getBody(), true)['email_delivery_ready']);
        $this->assertStringNotContainsString('saved-secret', json_encode(LogService::getLogs()));
    }

    public function testTransportExceptionsAreNotExposed(): void
    {
        $this->grant();
        $email = $this->createMock(Email::class);
        $email->method('send')->willThrowException(new \RuntimeException('smtp://internal password=saved-secret'));
        Services::injectMock('email', $email);
        $response = $this->call('testEmail', ['email' => 'recipient@example.test']);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('email_delivery_failed', json_decode($response->getBody(), true)['error']);
        $this->assertStringNotContainsString('saved-secret', $response->getBody() . json_encode(LogService::getLogs()));
    }

    private ?string $stepUpToken = null;

    private function grant(): void
    {
        $this->stepUpToken = (new StepUpAuthenticationService())->issue('settings.email.test', 'correct-password');
    }

    private function call(string $method, array $body, string $base = 'http://example.com/', string $index = '')
    {
        Services::resetSingle('response');
        Services::resetSingle('security');
        $config = clone config('App');
        $config->baseURL = $base;
        $config->indexPage = $index;
        $route = ['testEmail' => 'test-email', 'validateEmail' => 'validate-email'][$method] ?? '';
        $request = new IncomingRequest($config, new SiteURI($config, 'api/settings' . ($route ? '/' . $route : '')), null, new UserAgent());
        $request->setMethod('POST');
        $request->setGlobal('cookie', []);
        $request->setHeader('Content-Type', 'application/json');
        if ($this->stepUpToken !== null) $request->setHeader('X-Extplorer-Step-Up', $this->stepUpToken);
        $request->setBody(json_encode($body, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR));
        Services::injectMock('request', $request);
        $request->setHeader('X-CSRF-TOKEN', csrf_hash());
        $this->assertNull((new CsrfFilter())->before($request));
        $controller = new SettingsController();
        $controller->initController($request, Services::response(), Services::logger());
        $response = $controller->$method();
        (new CsrfHeaderFilter())->after($request, $response);
        $this->assertNotSame('', $response->getHeaderLine('X-CSRF-HASH'));
        return $response;
    }
}
