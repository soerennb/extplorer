<?php

namespace Tests\Unit;

use App\Filters\InstallFilter;
use App\Models\UserModel;
use CodeIgniter\Filters\Filters;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

/**
 * Only the installer routes are public. Filters receive the route path
 * without installation directory or index page, so no wildcard prefix is
 * needed, and a segment called "install" elsewhere must stay protected.
 */
final class InstallRouteExceptionTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
    }

    protected function tearDown(): void
    {
        $this->sandbox->close();
        parent::tearDown();
    }

    public static function deployments(): iterable
    {
        foreach (['http://example.com/', 'http://example.com/install/', 'http://example.com/extplorer/public/'] as $base) {
            foreach (['', 'index.php'] as $index) yield [$base, $index];
        }
    }

    #[DataProvider('deployments')]
    public function testOnlyInstallerRoutesSkipAuthentication(string $base, string $index): void
    {
        foreach (['install', 'install/create'] as $route) {
            $this->assertNotContains('auth', $this->beforeFilters($base, $index, $route), $route);
        }
        foreach (['api/mounts/install', 'api/mounts/Install', 'api/users/INSTALL/permissions',
            'api/upload/session/install/complete', 'public/install', 'x/public/install/y'] as $route) {
            $this->assertContains('auth', $this->beforeFilters($base, $index, $route), $route);
        }
    }

    #[DataProvider('deployments')]
    public function testInstalledApplicationOnlyRedirectsTheInstallerRoute(string $base, string $index): void
    {
        (new UserModel())->saveUsers([['username' => 'root', 'role' => 'admin', 'home_dir' => '/', 'auth_version' => 1]]);
        touch(config('Storage')->root . '/installed.lock');

        $this->assertNotNull((new InstallFilter())->before($this->request($base, $index, 'install')));
        foreach (['', 'api/mounts/install', 'login'] as $route) {
            $this->assertNull((new InstallFilter())->before($this->request($base, $index, $route)), $route);
        }
    }

    private function beforeFilters(string $base, string $index, string $route): array
    {
        $request = $this->request($base, $index, $route);
        $filters = new Filters(config('Filters'), $request, Services::response());
        $filters->initialize($request->getPath());
        return array_map(static fn($filter): string => is_string($filter) ? explode(':', $filter)[0] : (string)$filter,
            $filters->getFilters()['before']);
    }

    private function request(string $base, string $index, string $route): IncomingRequest
    {
        $config = clone config('App');
        $config->baseURL = $base;
        $config->indexPage = $index;
        return new IncomingRequest($config, new SiteURI($config, $route), null, new UserAgent());
    }
}
