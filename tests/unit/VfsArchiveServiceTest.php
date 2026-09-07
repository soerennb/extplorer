<?php

namespace Tests\Unit;

use App\Services\VfsArchiveService;
use App\Services\VFS\LocalAdapter;
use CodeIgniter\Test\CIUnitTestCase;

final class VfsArchiveServiceTest extends CIUnitTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/extplorer-vfs-archive-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/folder', 0700, true);
        file_put_contents($this->root . '/folder/large.txt', str_repeat('archive-data', 32));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
        parent::tearDown();
    }

    public function testArchiveKeepsStagedSourcesUntilZipIsClosed(): void
    {
        $destination = $this->root . '/result.zip';
        (new VfsArchiveService())->createZip(new LocalAdapter($this->root), ['folder'], $destination);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($destination) === true);
        $this->assertSame(str_repeat('archive-data', 32), $zip->getFromName('folder/large.txt'));
        $this->assertTrue($zip->close());
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
}
