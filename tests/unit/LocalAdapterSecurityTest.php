<?php

namespace Tests\Unit;

use App\Services\VFS\LocalAdapter;
use CodeIgniter\Test\CIUnitTestCase;

class LocalAdapterSecurityTest extends CIUnitTestCase
{
    public function testExtractBlocksZipSlipTraversalEntries(): void
    {
        $root = sys_get_temp_dir() . '/extplorer_zip_slip_' . uniqid('', true);
        $extractDir = $root . '/extract';
        $archivePath = $root . '/malicious.zip';
        $outsidePath = dirname($root) . '/zip-slip-pwned.txt';

        mkdir($extractDir, 0755, true);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true);
        $zip->addFromString('../zip-slip-pwned.txt', 'owned');
        $zip->close();

        $adapter = new LocalAdapter($root);

        try {
            $adapter->extract('malicious.zip', 'extract');
            $this->fail('Expected extraction to fail for zip-slip entry.');
        } catch (\Exception $e) {
            $this->assertStringContainsStringIgnoringCase('traversal', $e->getMessage());
        }

        $this->assertFileDoesNotExist($outsidePath);

        @unlink($archivePath);
        @unlink($outsidePath);
        @rmdir($extractDir);
        @rmdir($root);
    }

    public function testDeleteDoesNotFollowDirectorySymlinks(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlink creation is not reliably available on Windows CI.');
        }

        $root = sys_get_temp_dir() . '/extplorer_symlink_' . uniqid('', true);
        $victimDir = sys_get_temp_dir() . '/extplorer_victim_' . uniqid('', true);
        $managedDir = $root . '/managed';
        $linkPath = $managedDir . '/outside-link';
        $victimFile = $victimDir . '/keep.txt';

        mkdir($managedDir, 0755, true);
        mkdir($victimDir, 0755, true);
        file_put_contents($victimFile, 'must survive');

        if (!@symlink($victimDir, $linkPath)) {
            $this->markTestSkipped('Symlink creation is not permitted in this environment.');
        }

        $adapter = new LocalAdapter($root);
        $this->assertTrue($adapter->delete('managed'));

        $this->assertFileExists($victimFile);

        @unlink($victimFile);
        @rmdir($victimDir);
        @rmdir($root);
    }

    public function testCopyBlocksNestedDirectoryTarget(): void
    {
        $root = sys_get_temp_dir() . '/extplorer_copy_guard_' . uniqid('', true);
        $sourceDir = $root . '/folder';

        mkdir($sourceDir, 0755, true);
        file_put_contents($sourceDir . '/example.txt', 'data');

        $adapter = new LocalAdapter($root);

        try {
            $adapter->copy('folder', 'folder/copy');
            $this->fail('Expected nested directory copy to fail.');
        } catch (\Exception $e) {
            $this->assertStringContainsStringIgnoringCase('descendants', $e->getMessage());
        }

        @unlink($sourceDir . '/example.txt');
        @rmdir($sourceDir);
        @rmdir($root);
    }

    public function testDirectoryListingStopsBeforeMaterializingAllEntries(): void
    {
        $root = sys_get_temp_dir() . '/extplorer_listing_limit_' . uniqid('', true);
        mkdir($root, 0755, true);
        $previous = getenv('EXTPLORER_MAX_DIRECTORY_ENTRIES');
        putenv('EXTPLORER_MAX_DIRECTORY_ENTRIES=1');
        file_put_contents($root . '/one.txt', 'one');
        file_put_contents($root . '/two.txt', 'two');

        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Directory listing exceeds');
            (new LocalAdapter($root))->listDirectory('/');
        } finally {
            $previous === false ? putenv('EXTPLORER_MAX_DIRECTORY_ENTRIES') : putenv('EXTPLORER_MAX_DIRECTORY_ENTRIES=' . $previous);
            @unlink($root . '/one.txt');
            @unlink($root . '/two.txt');
            @rmdir($root);
        }
    }
}
