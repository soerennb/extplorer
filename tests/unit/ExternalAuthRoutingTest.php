<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\StepUpController;
use App\Controllers\Login;
use App\Filters\CsrfFilter;
use App\Filters\CsrfHeaderFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;

final class ExternalAuthRoutingTest extends CIUnitTestCase
{
    public static function deployments(): iterable
    {
        foreach (['http://example.com/', 'http://example.com/extplorer/public/'] as $base) {
            foreach (['', 'index.php'] as $index) yield [$base, $index];
        }
    }

    #[DataProvider('deployments')]
    public function testDavMethodsReachTheControllerInEveryInstallationLayout(string $base, string $index): void
    {
        $app = clone config('App');
        $app->baseURL = $base;
        $app->indexPage = $index;
        $routes = Services::routes(false);
        $routes->loadRoutes();
        foreach (['PROPFIND', 'PROPPATCH', 'MKCOL', 'MOVE', 'COPY', 'LOCK', 'UNLOCK', 'GET', 'PUT', 'DELETE'] as $method) {
            foreach (['dav/', 'dav/file.txt'] as $path) {
                $request = new IncomingRequest($app, new SiteURI($app, $path), null, new UserAgent());
                $request->setMethod($method);
                $router = Services::router($routes, $request, false);
                $router->handle($request->getPath());
                $this->assertSame('\\App\\Controllers\\DavController', $router->controllerName());
                $this->assertSame('index', $router->methodName());
            }
        }
    }

    #[DataProvider('deployments')]
    public function testOnlyExactServerEventPostsBypassBrowserCsrf(string $base, string $index): void
    {
        $app = clone config('App');
        $app->baseURL = $base;
        $app->indexPage = $index;
        foreach (['auth/events/company', 'auth/oidc/company/backchannel-logout'] as $path) {
            $request = new IncomingRequest($app, new SiteURI($app, $path), '{}', new UserAgent());
            $request->setMethod('POST');
            $this->assertTrue(\App\Services\ExternalAuth\ExternalEventRoutes::matches($request));
            $this->assertNull((new CsrfFilter())->before($request));
            $response = Services::response();
            $response->setStatusCode(400);
            (new CsrfHeaderFilter())->after($request, $response);
            $this->assertNotEmpty($response->getHeaderLine('X-CSRF-HASH'));
            $request->setMethod('GET');
            $this->assertFalse(\App\Services\ExternalAuth\ExternalEventRoutes::matches($request));
        }
        foreach (['logout/provider', 'auth/events/company/extra', 'api/auth/events/company'] as $path) {
            $request = new IncomingRequest($app, new SiteURI($app, $path), '{}', new UserAgent());
            $request->setMethod('POST');
            $this->assertFalse(\App\Services\ExternalAuth\ExternalEventRoutes::matches($request));
        }
    }

    #[DataProvider('deployments')]
    public function testLoginReturnUrlsNeverDuplicateTheInstallationOrIndexPrefix(string $base, string $index): void
    {
        $app = config('App');
        $originalBase = $app->baseURL;
        $originalIndex = $app->indexPage;
        $app->baseURL = $base;
        $app->indexPage = $index;
        $original = Services::request();
        Services::injectMock('request', new IncomingRequest($app, new SiteURI($app, 'login'), null, new UserAgent()));
        try {
            $expected = $base . ($index !== '' ? $index . '/' : '');
            $this->assertSame($expected, Login::returnUrl(Login::defaultReturnPath()));
            $path = parse_url($expected . 'admin?tab=users', PHP_URL_PATH) . '?tab=users';
            $this->assertSame($expected . 'admin?tab=users', Login::returnUrl($path));
            $this->assertSame($expected, redirect()->to(Login::returnUrl(Login::defaultReturnPath()))->getHeaderLine('Location'));
        } finally {
            Services::injectMock('request', $original);
            $app->baseURL = $originalBase;
            $app->indexPage = $originalIndex;
        }
    }

    #[DataProvider('deployments')]
    public function testExternalRoutesKeepTheCsrfVerifierAndRefreshHeader(string $base, string $index): void
    {
        $app = clone config('App');
        $app->baseURL = $base;
        $app->indexPage = $index;
        foreach (['api/security/step-up/exchange', 'api/security/step-up/cancel'] as $route) {
            $request = new IncomingRequest($app, new SiteURI($app, $route), '{}', new UserAgent());
            $request->setMethod('POST')->setHeader('Content-Type', 'application/json');
            $security = Services::security();
            $request->setGlobal('cookie', [$security->getCookieName() => $security->getHash()]);
            $request->setHeader($security->getHeaderName(), $security->getHash());
            $this->assertSame('/' . $route, '/' . trim($request->getPath(), '/'));
            $this->assertNull((new CsrfFilter())->before($request));
            $controller = new StepUpController();
            $response = Services::response();
            $controller->initController($request, $response, Services::logger());
            $response = str_ends_with($route, '/exchange') ? $controller->exchange() : $controller->cancel();
            (new CsrfHeaderFilter())->after($request, $response);
            $this->assertSame(422, $response->getStatusCode());
            $this->assertNotEmpty($response->getHeaderLine('X-CSRF-HASH'));
            $request->setHeader($security->getHeaderName(), 'invalid');
            try {
                (new CsrfFilter())->before($request);
                $this->fail('The new endpoints must reject invalid CSRF tokens.');
            } catch (\CodeIgniter\Security\Exceptions\SecurityException) {
                $this->assertTrue(true);
            }
        }
    }
}
