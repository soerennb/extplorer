<?php

namespace Tests\Unit;

use App\Controllers\ShareController;
use CodeIgniter\Test\CIUnitTestCase;

class ShareControllerSecurityTest extends CIUnitTestCase
{
    public function testSharePathResolutionRejectsTraversal(): void
    {
        $controller = new ShareController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('resolveSharePaths');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('traversal');
        $method->invoke($controller, ['path' => '../../etc/passwd']);
    }

    public function testShareUsageScanRejectsSymbolicLinks(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlink creation is not reliably available on Windows CI.');
        }

        $root = sys_get_temp_dir() . '/extplorer_share_usage_' . uniqid('', true);
        $outside = sys_get_temp_dir() . '/extplorer_share_usage_outside_' . uniqid('', true);
        mkdir($root, 0755, true);
        mkdir($outside, 0755, true);
        file_put_contents($outside . '/secret.txt', 'secret');

        if (!@symlink($outside, $root . '/link')) {
            $this->markTestSkipped('Symlink creation is not permitted in this environment.');
        }

        try {
            $reflection = new \ReflectionClass(new ShareController());
            $method = $reflection->getMethod('collectUsageStats');
            $method->setAccessible(true);

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('quota');
            $method->invoke(new ShareController(), $root);
        } finally {
            @unlink($root . '/link');
            @unlink($outside . '/secret.txt');
            @rmdir($outside);
            @rmdir($root);
        }
    }
}
