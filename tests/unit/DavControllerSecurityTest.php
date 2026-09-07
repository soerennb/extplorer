<?php

namespace Tests\Unit;

use App\Controllers\DavController;
use CodeIgniter\Test\CIUnitTestCase;

class DavControllerSecurityTest extends CIUnitTestCase
{
    private function callPrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    public function testResolveSafeDavRootPathRejectsTraversal(): void
    {
        $controller = new DavController();
        $baseRoot = config('Storage')->fileManagerRoot;
        @mkdir($baseRoot, 0755, true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid WebDAV home directory');
        $this->callPrivate($controller, 'resolveSafeDavRootPath', [$baseRoot, '../../etc/passwd']);
    }
}
