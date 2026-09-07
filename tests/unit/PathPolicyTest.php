<?php

namespace Tests\Unit;

use App\Services\VFS\DeniedFileSystem;
use App\Services\VFS\PathPolicy;
use App\Services\VFS\VfsFactory;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class PathPolicyTest extends CIUnitTestCase
{
    public function testTraversalSegmentsAreRejected(): void
    {
        $root = sys_get_temp_dir() . '/extplorer_path_policy_' . uniqid('', true);
        mkdir($root, 0755, true);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('traversal');
            PathPolicy::resolve($root, 'folder/../../outside.txt');
        } finally {
            @rmdir($root);
        }
    }

    /**
     * @dataProvider invalidRelativePathProvider
     */
    public function testInvalidRelativePathFormsAreRejected(string $path, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        PathPolicy::normalizeRelative($path);
    }

    public static function invalidRelativePathProvider(): array
    {
        return [
            'control character' => ["safe/secret\n.txt", 'Invalid path'],
            'unc path' => ['//server/share/file.txt', 'Invalid absolute path'],
            'drive absolute path' => ['C:/Windows/system.ini', 'Invalid absolute path'],
            'drive relative path' => ['C:Windows/system.ini', 'Invalid absolute path'],
        ];
    }

    public function testMountAliasesAreStrictAndBounded(): void
    {
        $this->assertSame('Remote Files', PathPolicy::normalizeMountAlias('Remote Files'));

        foreach (['', ' leading', 'trailing ', 'with/slash', "with\nline", str_repeat('a', 65)] as $alias) {
            try {
                PathPolicy::normalizeMountAlias($alias);
                $this->fail('Expected invalid mount alias to be rejected: ' . json_encode($alias));
            } catch (RuntimeException $exception) {
                $this->assertSame('Invalid mount alias.', $exception->getMessage());
            }
        }
    }

    public function testSiblingPrefixIsNotInsideRoot(): void
    {
        $this->assertFalse(PathPolicy::isWithinRoot('/srv/files', '/srv/files-old/secret.txt'));
        $this->assertTrue(PathPolicy::isWithinRoot('/srv/files', '/srv/files/secret.txt'));
    }

    public function testSymlinkComponentsAreRejected(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlink creation is not reliably available on Windows CI.');
        }

        $root = sys_get_temp_dir() . '/extplorer_path_symlink_' . uniqid('', true);
        $outside = sys_get_temp_dir() . '/extplorer_path_outside_' . uniqid('', true);
        mkdir($root, 0755, true);
        mkdir($outside, 0755, true);
        file_put_contents($outside . '/secret.txt', 'secret');

        if (!@symlink($outside, $root . '/link')) {
            $this->markTestSkipped('Symlink creation is not permitted in this environment.');
        }

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Symbolic links');
            PathPolicy::resolve($root, 'link/secret.txt');
        } finally {
            @unlink($root . '/link');
            @unlink($outside . '/secret.txt');
            @rmdir($outside);
            @rmdir($root);
        }
    }

    public function testVfsFactoryDeniesMissingAuthenticationScope(): void
    {
        $filesystem = VfsFactory::createFileSystem();

        $this->assertInstanceOf(DeniedFileSystem::class, $filesystem);
        $this->expectException(RuntimeException::class);
        $filesystem->listDirectory('/');
    }

    public function testVfsFactoryDeniesUnknownUserInsteadOfReturningRoot(): void
    {
        $filesystem = VfsFactory::createFileSystem('missing-user-' . uniqid());

        $this->assertInstanceOf(DeniedFileSystem::class, $filesystem);
        $this->expectException(RuntimeException::class);
        $filesystem->listDirectory('/');
    }
}
