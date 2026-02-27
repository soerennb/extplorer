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
}
