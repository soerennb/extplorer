<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\VFS\VirtualAdapter;
use App\Services\VFS\LocalAdapter;

class VirtualVfsTest extends CIUnitTestCase
{
    public function testMounts()
    {
        $vfs = new VirtualAdapter();
        $tmp = sys_get_temp_dir() . '/test_vfs_' . uniqid();
        if (!is_dir($tmp)) mkdir($tmp);
        if (!is_dir($tmp . '/sub')) mkdir($tmp . '/sub');
        file_put_contents($tmp . '/sub/file.txt', 'hello');

        $vfs->mount('Test', new LocalAdapter($tmp));

        // Test List Root
        $root = $vfs->listDirectory('/');
        $this->assertCount(1, $root);
        $this->assertEquals('Test', $root[0]['name']);

        // Test List Mount
        $items = $vfs->listDirectory('Test/sub');
        $this->assertCount(1, $items);
        // The path returned should be prefixed with mount alias
        // LocalAdapter returns 'sub/file.txt' (relative to its root) ??
        // Actually LocalAdapter logic: $relativePath = $path . '/' . $item;
        // So passed 'sub', returns 'sub/file.txt'.
        // VirtualAdapter prefixes 'Test/' -> 'Test/sub/file.txt'.
        $this->assertEquals('Test/sub/file.txt', $items[0]['path']);
        
        // Test Read
        $content = $vfs->readFile('Test/sub/file.txt');
        $this->assertEquals('hello', $content);

        // Test ResolvePath
        $phys = $vfs->resolvePath('Test/sub/file.txt');
        $this->assertEquals(realpath($tmp . '/sub/file.txt'), realpath($phys));

        // Clean up
        exec("rm -rf " . escapeshellarg($tmp));
    }
}
