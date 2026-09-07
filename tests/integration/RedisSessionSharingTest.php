<?php

namespace Tests\Integration;

use CodeIgniter\Session\Handlers\RedisHandler;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Session;

final class RedisSessionSharingTest extends CIUnitTestCase
{
    public function testTwoApplicationInstancesReadTheSameRedisSession(): void
    {
        if (getenv('EXTPLORER_RUN_REDIS_SESSION_TEST') !== '1') {
            $this->markTestSkipped('Redis session integration is enabled only in the Redis CI matrix.');
        }
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The phpredis extension is unavailable.');
        }

        $config = new Session();
        $first = new RedisHandler($config, '127.0.0.1');
        $second = new RedisHandler($config, '127.0.0.1');
        $sessionId = 'integration-' . bin2hex(random_bytes(12));

        $this->assertTrue($first->open('', 'ci_session'));
        $this->assertTrue($second->open('', 'ci_session'));
        $this->assertTrue($first->write($sessionId, 'shared-session-data'));
        $first->close();
        $this->assertSame('shared-session-data', $second->read($sessionId));

        $second->destroy($sessionId);
        $second->close();
    }
}
