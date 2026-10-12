<?php

namespace Tests\Unit;

use App\Filters\AuthFilter;
use App\Models\UserModel;
use App\Services\SettingsService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class AuthRoutingContractTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([['username' => 'alice', 'role' => 'user', 'auth_version' => 1]]);
        (new SettingsService())->saveSettings(['session_idle_timeout_minutes' => 0]);
        session()->set(['username' => 'alice', 'isLoggedIn' => true, 'auth_version' => 1,
            'connection' => ['mode' => 'local'], 'force_password_change' => true]);
    }

    protected function tearDown(): void
    {
        session()->destroy();
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
    public function testPasswordGateAndItsAllowedRoutesWithDisabledTimeout(string $base, string $index): void
    {
        foreach (['api/profile/password', 'api/profile/details', 'logout', ''] as $route) {
            $this->assertNull((new AuthFilter())->before($this->request($base, $index, $route)), $route);
        }
        $response = (new AuthFilter())->before($this->request($base, $index, 'api/ls'));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('password_change_required', json_decode($response->getBody(), true)['code']);
    }

    private function request(string $base, string $index, string $route): IncomingRequest
    {
        $config = clone config('App');
        $config->baseURL = $base;
        $config->indexPage = $index;
        return new IncomingRequest($config, new SiteURI($config, $route), null, new UserAgent());
    }
}
