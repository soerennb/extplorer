<?php

namespace Tests\Unit;

use CodeIgniter\Session\Handlers\RedisHandler;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Session;

final class SessionConfigurationTest extends CIUnitTestCase
{
    private array $environment = [];

    protected function tearDown(): void
    {
        foreach ($this->environment as $key => $value) {
            $value === null ? putenv($key) : putenv($key . '=' . $value);
        }
        parent::tearDown();
    }

    public function testRedisSessionConfigurationIsSharedByAllInstances(): void
    {
        $this->setEnvironment('EXTPLORER_SESSION_DRIVER', 'redis');
        $this->setEnvironment('EXTPLORER_REDIS_HOST', 'redis.internal');
        $this->setEnvironment('EXTPLORER_REDIS_PORT', '6380');
        $this->setEnvironment('EXTPLORER_REDIS_DATABASE', '4');
        $this->setEnvironment('EXTPLORER_REDIS_PASSWORD', 'not-used-in-assertions');

        $config = new Session();

        $this->assertSame(RedisHandler::class, $config->driver);
        $this->assertStringStartsWith('tcp://redis.internal:6380?', $config->savePath);
        $this->assertStringContainsString('database=4', $config->savePath);
    }

    public function testInvalidSessionDriverFailsClosed(): void
    {
        $this->setEnvironment('EXTPLORER_SESSION_DRIVER', 'fallback');

        $this->expectException(\RuntimeException::class);
        new Session();
    }

    private function setEnvironment(string $key, string $value): void
    {
        if (!array_key_exists($key, $this->environment)) {
            $previous = getenv($key);
            $this->environment[$key] = $previous === false ? null : $previous;
        }
        putenv($key . '=' . $value);
    }
}
