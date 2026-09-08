<?php

namespace Tests\Unit;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

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

    private function rememberEnvironment(string $key): void
    {
        if (!array_key_exists($key, $this->environment)) {
            $value = getenv($key);
            $this->environment[$key] = $value === false ? null : $value;
        }
    }
}
