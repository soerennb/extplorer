<?php

namespace Tests\Unit;

use App\Services\StorageBoundaryPolicy;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

final class StorageBoundaryPolicyTest extends CIUnitTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/extplorer-storage-boundary-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0700, true);
    }

    protected function tearDown(): void
    {
        @rmdir($this->root . '/outside');
        @rmdir($this->root);
        parent::tearDown();
    }

    public function testDefaultStorageConfigurationIsOutsideWebroot(): void
    {
        (new StorageBoundaryPolicy())->assertSafe();
        $this->assertTrue(true);
    }

    public function testWritablePathInsideWebrootIsRejected(): void
    {
        $publicRoot = $this->root . '/public';
        mkdir($publicRoot, 0700, true);

        $this->expectException(RuntimeException::class);
        (new StorageBoundaryPolicy())->assertOutsideWebroot($publicRoot . '/uploads', $publicRoot);
    }

    public function testWritablePathOutsideWebrootIsCanonicalized(): void
    {
        $publicRoot = $this->root . '/public';
        $outside = $this->root . '/outside';
        mkdir($publicRoot, 0700, true);
        mkdir($outside, 0700, true);

        $resolved = (new StorageBoundaryPolicy())->assertOutsideWebroot($outside . '/nested', $publicRoot);
        $this->assertSame($outside . '/nested', $resolved);
    }

    public function testConfiguredStoragePathCannotOverlapApplicationSource(): void
    {
        $storage = clone config('Storage');
        $storage->state = realpath(__DIR__ . '/../../app');

        $this->expectException(RuntimeException::class);
        (new StorageBoundaryPolicy())->assertSafe($storage);
    }

    public function testManagedStoragePathsCannotOverlapEachOther(): void
    {
        $storage = clone config('Storage');
        $storage->state = $storage->uploads;

        $this->expectException(RuntimeException::class);
        (new StorageBoundaryPolicy())->assertSafe($storage);
    }
}
