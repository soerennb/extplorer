<?php

namespace Tests\Unit;

use App\Services\RemoteEndpointPolicy;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class RemoteEndpointPolicyTest extends CIUnitTestCase
{
    /** @var array<string, string|null> */
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'EXTPLORER_REMOTE_LOGIN_ENABLED',
            'EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST',
            'EXTPLORER_REMOTE_ALLOW_PRIVATE_TARGETS',
            'EXTPLORER_REMOTE_SECURITY_MODE',
        ] as $key) {
            $value = getenv($key);
            $this->environment[$key] = $value === false ? null : $value;
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $key => $value) {
            if ($value === null) {
                putenv($key);
            } else {
                putenv("{$key}={$value}");
            }
        }
        parent::tearDown();
    }

    public function testEmptyAllowlistDeniesEvenPublicTargets(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=compat');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not allowlisted');
        (new RemoteEndpointPolicy(static fn(string $host): array => ['8.8.8.8']))
            ->authorize('ftp', 'files.example.com', 21);
    }

    public function testExactProtocolHostAndPortAreRequired(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=strict');
        putenv('EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST=sftp://files.example.com:22');
        $policy = new RemoteEndpointPolicy(static fn(string $host): array => ['8.8.8.8']);

        $endpoint = $policy->authorize('sftp', 'files.example.com', 22, 'aa:bb');
        $this->assertSame('8.8.8.8', $endpoint['connect_host']);
        $this->assertSame('files.example.com', $endpoint['host']);

        $this->expectException(RuntimeException::class);
        $policy->authorize('sftp', 'files.example.com', 2022, 'aa:bb');
    }

    public function testPrivateResolvedTargetsAreRejectedByDefault(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=compat');
        putenv('EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST=ftp://files.example.com:21');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('private target');
        (new RemoteEndpointPolicy(static fn(string $host): array => ['10.0.0.5']))
            ->authorize('ftp', 'files.example.com', 21);
    }

    public function testDirectRemoteLoginIsDisabledByDefaultAndCanBeExplicitlyEnabled(): void
    {
        $policy = new RemoteEndpointPolicy();
        $this->assertFalse($policy->directLoginEnabled());

        putenv('EXTPLORER_REMOTE_LOGIN_ENABLED=1');
        $this->assertTrue($policy->directLoginEnabled());
    }

    public function testEndpointParserRejectsWildcardsCredentialsAndMissingPorts(): void
    {
        $policy = new RemoteEndpointPolicy();

        $this->assertNull($policy->parseEndpoint('sftp://*.example.com:22'));
        $this->assertNull($policy->parseEndpoint('sftp://user:pass@example.com:22'));
        $this->assertNull($policy->parseEndpoint('sftp://files.example.com'));
        $this->assertSame(
            ['protocol' => 'sftp', 'host' => 'files.example.com', 'port' => 22],
            $policy->parseEndpoint('sftp://files.example.com:22')
        );
    }
}
