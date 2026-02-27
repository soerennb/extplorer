<?php

namespace Tests\Unit;

use App\Controllers\Login;
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
}

