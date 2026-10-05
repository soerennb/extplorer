<?php

namespace Tests\Unit;

use App\Filters\CsrfHeaderFilter;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;

final class CsrfHeaderFilterTest extends CIUnitTestCase
{
    private array $cookiesBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cookiesBackup = service('superglobals')->getCookieArray();
        Services::resetSingle('request');
        Services::resetSingle('response');
        Services::resetSingle('security');
    }

    protected function tearDown(): void
    {
        service('superglobals')->setCookieArray($this->cookiesBackup);
        Services::resetSingle('request');
        Services::resetSingle('response');
        Services::resetSingle('security');
        parent::tearDown();
    }

    public static function deployments(): iterable
    {
        yield 'root' => ['http://example.com/', ''];
        yield 'subdirectory' => ['http://example.com/extplorer/public/', ''];
        yield 'root with index.php' => ['http://example.com/', 'index.php'];
        yield 'subdirectory with index.php' => ['http://example.com/extplorer/public/', 'index.php'];
    }

    public static function protectedResponses(): iterable
    {
        foreach (self::deployments() as $deployment => [$baseURL, $indexPage]) {
            foreach (['api/settings', 's/share-token/auth'] as $route) {
                foreach ([200, 428, 400] as $status) {
                    yield "{$deployment}: {$route} ({$status})" => [$baseURL, $indexPage, $route, $status];
                }
            }
        }
    }

    #[DataProvider('protectedResponses')]
    public function testApiAndShareResponsesRefreshCsrfRegardlessOfStatus(
        string $baseURL,
        string $indexPage,
        string $route,
        int $status
    ): void {
        $request = $this->request($baseURL, $indexPage, $route);
        Services::injectMock('request', $request);
        $response = Services::response()->setStatusCode($status);

        $this->assertSame($response, (new CsrfHeaderFilter())->after($request, $response));
        $this->assertNotSame('', $response->getHeaderLine('X-CSRF-HASH'));
        $this->assertSame($status, $response->getStatusCode());
    }

    public static function unprotectedRoutes(): iterable
    {
        foreach (self::deployments() as $deployment => [$baseURL, $indexPage]) {
            foreach (['', 'admin', 'login', 'files/api/settings'] as $route) {
                yield "{$deployment}: {$route}" => [$baseURL, $indexPage, $route];
            }
        }
        yield 'installation prefix api is not an API route' => ['http://example.com/api/', '', 'admin'];
        yield 'installation prefix s is not a share route' => ['http://example.com/s/', '', 'login'];
    }

    #[DataProvider('unprotectedRoutes')]
    public function testOtherRoutesDoNotReceiveCsrfHeader(string $baseURL, string $indexPage, string $route): void
    {
        $request = $this->request($baseURL, $indexPage, $route);
        $response = Services::response();

        $this->assertSame($response, (new CsrfHeaderFilter())->after($request, $response));
        $this->assertFalse($response->hasHeader('X-CSRF-HASH'));
    }

    public function testCliRequestIsUnchanged(): void
    {
        $request = new CLIRequest(config('App'));
        $response = Services::response();

        $this->assertSame($response, (new CsrfHeaderFilter())->after($request, $response));
        $this->assertFalse($response->hasHeader('X-CSRF-HASH'));
    }

    #[DataProvider('deployments')]
    public function testRegeneratedCookieAndResponseTokenAllowFollowingRequests(string $baseURL, string $indexPage): void
    {
        $this->assertTrue(config('Security')->tokenRandomize);
        $this->assertTrue(config('Security')->regenerate);
        $this->assertSame('cookie', config('Security')->csrfProtection);

        $request = $this->request($baseURL, $indexPage, 'admin');
        $request->setGlobal('cookie', []);
        Services::injectMock('request', $request);
        $token = $originalToken = csrf_hash();
        $cookieName = service('security')->getCookieName();
        $cookie = Services::response()->getCookie($cookieName)->getValue();

        // Emulate a browser applying Set-Cookie and X-CSRF-HASH between requests.
        // A fresh Security service must restore the hash from the latest cookie.
        foreach ([['api/settings', 428], ['api/security/step-up', 200], ['api/settings', 200]] as [$route, $status]) {
            $request = $this->request($baseURL, $indexPage, $route);
            $request->setMethod('POST');
            $request->setGlobal('cookie', [$cookieName => $cookie]);
            $request->setHeader('X-CSRF-TOKEN', $token);
            Services::injectMock('request', $request);
            Services::resetSingle('response');
            Services::resetSingle('security');

            $this->assertNull((new CSRF())->before($request));
            $response = Services::response()->setStatusCode($status);
            (new CsrfHeaderFilter())->after($request, $response);

            $nextCookie = $response->getCookie($cookieName)->getValue();
            $this->assertNotSame($cookie, $nextCookie);
            $cookie = $nextCookie;
            $token = $response->getHeaderLine('X-CSRF-HASH');
            $this->assertNotSame('', $token);
        }

        $request = $this->request($baseURL, $indexPage, 'api/settings');
        $request->setMethod('POST');
        $request->setGlobal('cookie', [$cookieName => $cookie]);
        $request->setHeader('X-CSRF-TOKEN', $originalToken);
        Services::injectMock('request', $request);
        Services::resetSingle('security');

        $this->expectException(SecurityException::class);
        (new CSRF())->before($request);
    }

    private function request(string $baseURL, string $indexPage, string $route): IncomingRequest
    {
        $config = clone config('App');
        $config->baseURL = $baseURL;
        $config->indexPage = $indexPage;

        return new IncomingRequest($config, new SiteURI($config, $route), null, new UserAgent());
    }
}
