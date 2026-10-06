<?php

namespace Tests\Unit;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\NativeBaseUrlResolver;

final class AppSecurityTest extends CIUnitTestCase
{
    private array $environment = [];

    protected function tearDown(): void
    {
        foreach ($this->environment as $key => $value) {
            $value === null ? putenv($key) : putenv($key . '=' . $value);
        }
        parent::tearDown();
    }

    public function testTrustedProxyCidrsAreExplicitlyConfigured(): void
    {
        $this->rememberEnvironment('EXTPLORER_TRUSTED_PROXY_IPS');
        putenv('EXTPLORER_TRUSTED_PROXY_IPS=10.0.0.0/24,2001:db8::/32');

        $config = new App();

        $this->assertSame('X-Forwarded-For', $config->proxyIPs['10.0.0.0/24']);
        $this->assertSame('X-Forwarded-For', $config->proxyIPs['2001:db8::/32']);
    }

    public function testCatchAllTrustedProxyCidrIsRejected(): void
    {
        $this->rememberEnvironment('EXTPLORER_TRUSTED_PROXY_IPS');
        putenv('EXTPLORER_TRUSTED_PROXY_IPS=0.0.0.0/0');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid IP or CIDR');
        new App();
    }

    public function testTrustedForwardedHttpsSchemeIsRecognized(): void
    {
        $config = new App();
        $config->proxyIPs = ['10.0.0.0/24' => 'X-Forwarded-For'];
        $superglobals = service('superglobals');
        $originalServer = $superglobals->getServerArray();

        try {
            $superglobals->setServer('REMOTE_ADDR', '10.0.0.10');
            $superglobals->unsetServer('HTTPS');

            $request = new IncomingRequest(
                $config,
                new URI('http://files.example.test/login'),
                null,
                new UserAgent()
            );
            $request->setHeader('X-Forwarded-Proto', 'https');

            $this->assertTrue($request->isSecure());
        } finally {
            $superglobals->setServerArray($originalServer);
        }
    }

    public function testForwardedHttpsSchemeFromUntrustedSourceIsIgnored(): void
    {
        $config = new App();
        $config->proxyIPs = ['10.0.0.0/24' => 'X-Forwarded-For'];
        $superglobals = service('superglobals');
        $originalServer = $superglobals->getServerArray();

        try {
            $superglobals->setServer('REMOTE_ADDR', '192.0.2.10');
            $superglobals->unsetServer('HTTPS');

            $request = new IncomingRequest(
                $config,
                new URI('http://files.example.test/login'),
                null,
                new UserAgent()
            );
            $request->setHeader('X-Forwarded-Proto', 'https');

            $this->assertFalse($request->isSecure());
        } finally {
            $superglobals->setServerArray($originalServer);
        }
    }

    public function testConfiguredBaseUrlDefinesTrustedHost(): void
    {
        $this->rememberEnvironment('EXTPLORER_BASE_URL');
        putenv('EXTPLORER_BASE_URL=https://files.example.test/base/');

        $config = new App();

        $this->assertSame('https://files.example.test/base/', $config->baseURL);
        $this->assertSame(['files.example.test'], $config->allowedHostnames);
    }

    public function testConfiguredBaseUrlRejectsEmbeddedCredentials(): void
    {
        $this->rememberEnvironment('EXTPLORER_BASE_URL');
        putenv('EXTPLORER_BASE_URL=https://user:secret@files.example.test/');

        $this->expectException(\RuntimeException::class);
        new App();
    }

    public function testCanonicalServerNameBuildsBaseUrlInsteadOfRequestHost(): void
    {
        $resolved = $this->resolveServerBaseUrl([
            'SERVER_NAME' => 'files.example.test',
            'HTTP_HOST' => 'attacker.example.test',
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
            'SCRIPT_NAME' => '/index.php',
        ]);

        $this->assertSame([
            'url' => 'https://files.example.test/',
            'scheme' => 'https',
            'host' => 'files.example.test',
        ], $resolved);
    }

    public function testAppUsesCanonicalServerNameWhenExplicitBaseUrlIsUnset(): void
    {
        foreach (['EXTPLORER_BASE_URL', 'app.baseURL', 'app_baseURL'] as $key) {
            $this->rememberEnvironment($key);
            putenv($key);
        }

        $originalServer = $_SERVER;
        $_SERVER = [
            'SERVER_NAME' => 'files.example.test',
            'HTTP_HOST' => 'attacker.example.test',
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
            'SCRIPT_NAME' => '/index.php',
        ];

        try {
            $config = new App();
        } finally {
            $_SERVER = $originalServer;
        }

        $this->assertSame('https://files.example.test/', $config->baseURL);
        $this->assertSame(['files.example.test'], $config->allowedHostnames);
    }

    public function testCanonicalServerNamePreservesNativeSubdirectory(): void
    {
        $resolved = $this->resolveServerBaseUrl([
            'SERVER_NAME' => 'files.example.test',
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
            'SCRIPT_NAME' => '/extplorer/public/index.php',
        ]);

        $this->assertSame('https://files.example.test/extplorer/public/', $resolved['url']);
    }

    public function testCanonicalIpv6ServerNameIsBracketedInTheUrlAndAllowedHost(): void
    {
        $resolved = $this->resolveServerBaseUrl([
            'SERVER_NAME' => '::1',
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
            'SCRIPT_NAME' => '/index.php',
        ]);

        $this->assertSame('https://[::1]/', $resolved['url']);
        $this->assertSame('[::1]', $resolved['host']);
    }

    public function testCanonicalServerNameIncludesNonstandardHttpsPort(): void
    {
        $resolved = $this->resolveServerBaseUrl([
            'SERVER_NAME' => 'files.example.test',
            'HTTPS' => 'on',
            'SERVER_PORT' => '8443',
            'SCRIPT_NAME' => '/index.php',
        ]);

        $this->assertSame('https://files.example.test:8443/', $resolved['url']);
    }

    public function testForwardedHttpsIsUsedOnlyFromConfiguredProxy(): void
    {
        $server = [
            'SERVER_NAME' => 'files.example.test',
            'REMOTE_ADDR' => '10.0.0.12',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '9443',
            'SERVER_PORT' => '80',
            'SCRIPT_NAME' => '/index.php',
        ];

        $trusted = $this->resolveServerBaseUrl($server, ['10.0.0.0/24']);
        $untrusted = $this->resolveServerBaseUrl($server, ['192.0.2.0/24']);

        $this->assertSame('https://files.example.test/', $trusted['url']);
        $this->assertSame('http://files.example.test/', $untrusted['url']);
    }

    public function testCanonicalServerNameRejectsRequestHostsAndWildcards(): void
    {
        foreach (['_', '*.example.test', 'files.example.test:443', 'files..example.test'] as $serverName) {
            $this->assertNull($this->resolveServerBaseUrl([
                'SERVER_NAME' => $serverName,
                'HTTP_HOST' => 'files.example.test',
                'HTTPS' => 'on',
                'SCRIPT_NAME' => '/index.php',
            ]));
        }
    }

    public function testServerNameRejectsInvalidScriptPaths(): void
    {
        $this->assertNull($this->resolveServerBaseUrl([
            'SERVER_NAME' => 'files.example.test',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/extplorer/%2e%2e/index.php',
        ]));
    }

    public function testProductionUrlPolicyAllowsHttpsAndLoopbackHttpOnly(): void
    {
        $method = (new \ReflectionClass(App::class))->getMethod('productionUrlAllowed');
        $config = new App();

        $this->assertTrue($method->invoke($config, 'https', 'files.example.test'));
        $this->assertTrue($method->invoke($config, 'http', '127.0.0.1'));
        $this->assertTrue($method->invoke($config, 'http', 'localhost'));
        $this->assertFalse($method->invoke($config, 'http', 'files.example.test'));
    }

    public function testProductionBaseUrlRequirementExcludesCliOnly(): void
    {
        $method = (new \ReflectionClass(App::class))->getMethod('baseUrlRequiredForContext');
        $config = new App();

        $this->assertFalse($method->invoke($config, true, 'cli'));
        $this->assertTrue($method->invoke($config, true, 'fpm-fcgi'));
        $this->assertFalse($method->invoke($config, false, 'fpm-fcgi'));
    }

    private function rememberEnvironment(string $key): void
    {
        if (!array_key_exists($key, $this->environment)) {
            $value = getenv($key);
            $this->environment[$key] = $value === false ? null : $value;
        }
    }

    private function resolveServerBaseUrl(array $server, array $trustedProxies = []): ?array
    {
        return NativeBaseUrlResolver::resolve($server, $trustedProxies);
    }
}
