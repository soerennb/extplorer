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

    public function testZipExtractionRejectsWindowsAbsoluteAndDriveRelativeEntries(): void
    {
        foreach (['C:Windows/system.ini', 'C:/Windows/system.ini', '\\server\\share\\secret.txt'] as $entryName) {
            $root = sys_get_temp_dir() . '/extplorer_zip_absolute_' . uniqid('', true);
            mkdir($root . '/extract', 0755, true);
            $archivePath = $root . '/malicious.zip';
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true);
            $zip->addFromString($entryName, 'owned');
            $zip->close();

            try {
                $this->expectException(\Exception::class);
                (new LocalAdapter($root))->extract('malicious.zip', 'extract');
            } finally {
                @unlink($archivePath);
                @rmdir($root . '/extract');
                @rmdir($root);
            }
        }
    }

    public function testZipExtractionRejectsPreExistingSymlinkParentBeforeWriting(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlink creation is not reliably available on Windows CI.');
        }

        $root = sys_get_temp_dir() . '/extplorer_zip_parent_link_' . uniqid('', true);
        $outside = sys_get_temp_dir() . '/extplorer_zip_parent_outside_' . uniqid('', true);
        mkdir($root . '/extract', 0755, true);
        mkdir($outside, 0755, true);
        if (!@symlink($outside, $root . '/extract/link')) {
            $this->markTestSkipped('Symlink creation is not permitted in this environment.');
        }

        $archivePath = $root . '/payload.zip';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true);
        $zip->addFromString('link/payload.txt', 'must not escape');
        $zip->close();

        try {
            $this->expectException(\Exception::class);
            (new LocalAdapter($root))->extract('payload.zip', 'extract');
        } finally {
            $this->assertFileDoesNotExist($outside . '/payload.txt');
            @unlink($archivePath);
            @unlink($root . '/extract/link');
            @rmdir($outside);
            @rmdir($root . '/extract');
            @rmdir($root);
        }
    }

    public function testZipQuotaFailureRemovesNewExtractionOutput(): void
    {
        $root = sys_get_temp_dir() . '/extplorer_zip_cleanup_' . uniqid('', true);
        mkdir($root, 0755, true);
        $archivePath = $root . '/limited.zip';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true);
        $zip->addFromString('one.txt', 'one');
        $zip->addFromString('two.txt', 'two');
        $zip->close();

        $previous = getenv('EXTPLORER_ARCHIVE_MAX_ENTRIES');
        putenv('EXTPLORER_ARCHIVE_MAX_ENTRIES=1');
        try {
            $this->expectException(\Exception::class);
            (new LocalAdapter($root))->extract('limited.zip', 'new-destination');
        } finally {
            $previous === false
                ? putenv('EXTPLORER_ARCHIVE_MAX_ENTRIES')
                : putenv('EXTPLORER_ARCHIVE_MAX_ENTRIES=' . $previous);
            $this->assertDirectoryDoesNotExist($root . '/new-destination');
            @unlink($archivePath);
            @rmdir($root);
        }
    }

    public function testTarExtractionUsesTheSameBoundedPathPolicy(): void
    {
        $root = sys_get_temp_dir() . '/extplorer_tar_extract_' . uniqid('', true);
        mkdir($root, 0755, true);
        $archivePath = $root . '/payload.tar';
        $phar = new \PharData($archivePath);
        $phar->addEmptyDir('folder');
        $phar->addFromString('folder/file.txt', 'tar payload');

        try {
            $this->assertTrue((new LocalAdapter($root))->extract('payload.tar', 'extract'));
            $this->assertSame('tar payload', file_get_contents($root . '/extract/folder/file.txt'));
        } finally {
            $this->removeTree($root);
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $child = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($child) && !is_link($child)) {
                $this->removeTree($child);
            } else {
                @unlink($child);
            }
        }
        @rmdir($path);
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
