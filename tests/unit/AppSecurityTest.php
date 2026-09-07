<?php

namespace Tests\Unit;

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

    private function rememberEnvironment(string $key): void
    {
        if (!array_key_exists($key, $this->environment)) {
            $value = getenv($key);
            $this->environment[$key] = $value === false ? null : $value;
        }
    }
}
