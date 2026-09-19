<?php

namespace Tests\Unit;

use App\Services\StagingResourceService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

final class StagingResourceServiceTest extends CIUnitTestCase
{
    private string $root;
    /** @var array<string, string|false> */
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/extplorer-staging-resources-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0700, true);
        foreach (['EXTPLORER_UPLOAD_STAGING_MAX_MB', 'EXTPLORER_UPLOAD_STAGING_MAX_FILES'] as $key) {
            $this->environment[$key] = getenv($key);
        }
        putenv('EXTPLORER_UPLOAD_STAGING_MAX_MB=1');
        putenv('EXTPLORER_UPLOAD_STAGING_MAX_FILES=2');
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $key => $value) {
            $value === false ? putenv($key) : putenv($key . '=' . $value);
        }
        @unlink($this->root . '/reservations.php');
        @unlink($this->root . '/reservations.php.lock');
        @rmdir($this->root);
        parent::tearDown();
    }

    public function testReservationsAreOwnerScopedAndAggregateLimited(): void
    {
        $service = new StagingResourceService($this->root . '/reservations.php');
        $service->reserve('alice', 'upload:first', 700 * 1024, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('resource limit');
        $service->reserve('alice', 'upload:second', 400 * 1024, 1);
    }

    public function testReservationCanBeReplacedAndReleased(): void
    {
        $service = new StagingResourceService($this->root . '/reservations.php');
        $service->reserve('alice', 'upload:first', 700 * 1024, 1);
        $service->reserve('alice', 'upload:first', 800 * 1024, 1);
        $this->assertSame(800 * 1024, $service->get('upload:first')['bytes']);

        $service->release('upload:first', 'alice');
        $this->assertNull($service->get('upload:first'));
    }

    public function testExpiredReservationsAreCleaned(): void
    {
        $service = new StagingResourceService($this->root . '/reservations.php');
        $service->reserve('alice', 'transfer:expired', 1, 1, [], time() - 1);

        $expired = $service->cleanupExpired();
        $this->assertCount(1, $expired);
        $this->assertSame('transfer:expired', $expired[0]['id']);
        $this->assertNull($service->get('transfer:expired'));
    }
}
