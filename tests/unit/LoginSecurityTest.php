<?php

namespace Tests\Unit;

use App\Controllers\Login;
use Config\Services;
use CodeIgniter\Test\CIUnitTestCase;

class LoginSecurityTest extends CIUnitTestCase
{
    private function callPrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    public function testAssertRemoteHostAllowedBlocksLoopbackByDefault(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('private or reserved');

        $controller = new Login();
        $this->callPrivate($controller, 'assertRemoteHostAllowed', ['127.0.0.1', []]);
    }

    public function testAssertRemoteHostAllowedAllowsPublicIpByDefault(): void
    {
        $controller = new Login();
        $this->callPrivate($controller, 'assertRemoteHostAllowed', ['8.8.8.8', []]);
        $this->assertTrue(true);
    }

    public function testSafeReturnPathAllowsInternalRelativePath(): void
    {
        $controller = new Login();

        $this->assertSame('/api/ls?path=%2Fdocs', $this->callPrivate($controller, 'safeReturnPath', ['/api/ls?path=%2Fdocs']));
    }

    public function testSafeReturnPathBlocksExternalAndHeaderInjectionTargets(): void
    {
        $controller = new Login();

        $this->assertSame('/', $this->callPrivate($controller, 'safeReturnPath', ['https://evil.example']));
        $this->assertSame('/', $this->callPrivate($controller, 'safeReturnPath', ['//evil.example']));
        $this->assertSame('/', $this->callPrivate($controller, 'safeReturnPath', ["/app\nLocation: https://evil.example"]));
    }

    public function testPreferredLoginLocaleUsesBrowserLanguageBaseMatch(): void
    {
        $controller = new Login();
        $request = Services::request();
        $request->setHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.2');
        $controller->initController($request, Services::response(), Services::logger());

        $this->assertSame('de', $this->callPrivate($controller, 'preferredLoginLocale'));
    }

    public function testPreferredLoginLocaleFallsBackToEnglish(): void
    {
        $controller = new Login();
        $request = Services::request();
        $request->setHeader('Accept-Language', 'es-ES,es;q=0.9');
        $controller->initController($request, Services::response(), Services::logger());

        $this->assertSame('en', $this->callPrivate($controller, 'preferredLoginLocale'));
    }

    public function testLoginTranslationsUseSelectedLocaleWithEnglishFallback(): void
    {
        $controller = new Login();
        $messages = $this->callPrivate($controller, 'loginTranslations', ['de']);

        $this->assertSame('Anmelden', $messages['login_submit']);
        $this->assertSame('eXtplorer 3', $messages['app_name']);
    }
}
