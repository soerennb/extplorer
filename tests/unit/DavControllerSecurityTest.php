<?php

namespace Tests\Unit;

use App\Controllers\DavController;
use App\Services\Dav\SafeDirectory;
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

    public function testSafeDirectoryRejectsSymlinkEscape(): void
    {
        $root = sys_get_temp_dir() . '/extplorer-dav-' . bin2hex(random_bytes(8));
        $outside = sys_get_temp_dir() . '/extplorer-dav-outside-' . bin2hex(random_bytes(8));
        mkdir($root, 0700, true);
        mkdir($outside, 0700, true);
        file_put_contents($outside . '/secret.txt', 'secret');
        symlink($outside, $root . '/escape');

        try {
            $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
            (new SafeDirectory($root, $root))->getChild('escape');
        } finally {
            unlink($root . '/escape');
            unlink($outside . '/secret.txt');
            rmdir($outside);
            rmdir($root);
        }
    }
}
