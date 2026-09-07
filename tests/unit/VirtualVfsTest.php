<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\VFS\VirtualAdapter;
use App\Services\VFS\LocalAdapter;
use RuntimeException;

class VirtualVfsTest extends CIUnitTestCase
{
    private function assertTraversalBlocked(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected mounted path traversal to be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('traversal', strtolower($exception->getMessage()));
        }
    }

    private function deleteTree(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path)) {
            @unlink($path);
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $this->deleteTree($path . DIRECTORY_SEPARATOR . $item);
        }

        @rmdir($path);
    }

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
        $this->deleteTree($tmp);
    }

    public function testInvalidAndDuplicateMountAliasesAreRejected(): void
    {
        $root = sys_get_temp_dir() . '/test_vfs_alias_' . uniqid('', true);
        mkdir($root, 0755, true);

        try {
            $vfs = new VirtualAdapter();
            $adapter = new LocalAdapter($root);
            $vfs->mount('Files', $adapter);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('already exists');
            $vfs->mount('files', new LocalAdapter($root));
        } finally {
            $this->deleteTree($root);
        }
    }

    public function testMalformedMountAliasIsRejected(): void
    {
        $root = sys_get_temp_dir() . '/test_vfs_invalid_alias_' . uniqid('', true);
        mkdir($root, 0755, true);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Invalid mount alias');
            (new VirtualAdapter())->mount('../escape', new LocalAdapter($root));
        } finally {
            $this->deleteTree($root);
        }
    }

    public function testCopyCanCopyDirectoriesAcrossMounts(): void
    {
        $sourceRoot = sys_get_temp_dir() . '/test_vfs_src_' . uniqid('', true);
        $targetRoot = sys_get_temp_dir() . '/test_vfs_dst_' . uniqid('', true);

        mkdir($sourceRoot . '/docs/nested', 0755, true);
        mkdir($targetRoot, 0755, true);
        file_put_contents($sourceRoot . '/docs/readme.txt', 'hello');
        file_put_contents($sourceRoot . '/docs/nested/info.txt', 'world');

        $vfs = new VirtualAdapter();
        $vfs->mount('Src', new LocalAdapter($sourceRoot));
        $vfs->mount('Dst', new LocalAdapter($targetRoot));

        $this->assertTrue($vfs->copy('Src/docs', 'Dst/docs-copy'));
        $this->assertSame('hello', file_get_contents($targetRoot . '/docs-copy/readme.txt'));
        $this->assertSame('world', file_get_contents($targetRoot . '/docs-copy/nested/info.txt'));

        $this->deleteTree($sourceRoot);
        $this->deleteTree($targetRoot);
    }

    public function testMountedPathTraversalIsRejectedBeforeAnyOperationReachesTheAdapter(): void
    {
        $root = sys_get_temp_dir() . '/test_vfs_traversal_' . uniqid('', true);
        mkdir($root, 0755, true);
        file_put_contents($root . '/inside.txt', 'inside');

        $vfs = new VirtualAdapter();
        $vfs->mount('Test', new LocalAdapter($root));

        $operations = [
            static fn() => $vfs->listDirectory('Test/../outside'),
            static fn() => $vfs->readFile('Test/../outside.txt'),
            static fn() => $vfs->openReadStream('Test\\..\\outside.txt'),
            static fn() => $vfs->writeFile('Test/../outside.txt', 'blocked'),
            static fn() => $vfs->delete('Test/../outside.txt'),
            static fn() => $vfs->createDirectory('Test/../outside'),
            static fn() => $vfs->move('Test/../outside.txt', 'Test/inside.txt'),
            static fn() => $vfs->move('Test/inside.txt', 'Test/../outside.txt'),
            static fn() => $vfs->copy('Test/../outside.txt', 'Test/copy.txt'),
            static fn() => $vfs->copy('Test/inside.txt', 'Test/../copy.txt'),
            static fn() => $vfs->getMetadata('Test/../outside.txt'),
            static fn() => $vfs->chmod('Test/../outside.txt', 0644),
            static fn() => $vfs->chown('Test/../outside.txt', 'user', 'group'),
            static fn() => $vfs->getDirectorySize('Test/../outside'),
            static fn() => $vfs->archive(['Test/../outside.txt'], 'Test/archive.zip'),
            static fn() => $vfs->extract('Test/archive.zip', 'Test/../outside'),
            static fn() => $vfs->resolvePath('Test\\..\\outside.txt'),
        ];

        foreach ($operations as $operation) {
            $this->assertTraversalBlocked($operation);
        }

        $this->assertFileDoesNotExist(dirname($root) . '/outside.txt');
        $this->assertFileDoesNotExist($root . '/outside.txt');
        $this->assertFileExists($root . '/inside.txt');

        $this->deleteTree($root);
    }
}
