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

    public function testResolveSafeDavRootPathStaysInsideBaseRoot(): void
    {
        $controller = new DavController();
        $baseRoot = WRITEPATH . 'file_manager_root';
        @mkdir($baseRoot, 0755, true);

        $path = (string)$this->callPrivate($controller, 'resolveSafeDavRootPath', [$baseRoot, '../../etc/passwd']);
        $baseReal = rtrim((string)realpath($baseRoot), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $pathReal = rtrim((string)realpath($path) ?: $path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->assertStringStartsWith($baseReal, $pathReal);
    }
}

