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
}

