<?php

namespace Tests\Unit;

use App\Services\ResourcePolicy;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

final class ResourcePolicyTest extends CIUnitTestCase
{
    private string|false $previousLimit = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousLimit = getenv('EXTPLORER_MAX_DOWNLOAD_MB');
        putenv('EXTPLORER_MAX_DOWNLOAD_MB=1');
    }

    protected function tearDown(): void
    {
        if ($this->previousLimit === false) {
            putenv('EXTPLORER_MAX_DOWNLOAD_MB');
        } else {
            putenv('EXTPLORER_MAX_DOWNLOAD_MB=' . $this->previousLimit);
        }
        parent::tearDown();
    }

    public function testStreamCopyEnforcesConfiguredLimit(): void
    {
        $source = fopen('php://memory', 'rb+');
        $target = fopen('php://memory', 'wb+');
        $this->assertIsResource($source);
        $this->assertIsResource($target);
        fwrite($source, str_repeat('x', 1024 * 1024 + 1));
        rewind($source);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('resource limit');
            (new ResourcePolicy())->copyStream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }
    }

    public function testInvalidLimitConfigurationFailsClosed(): void
    {
        putenv('EXTPLORER_MAX_DOWNLOAD_MB=0');

        $this->expectException(RuntimeException::class);
        (new ResourcePolicy())->maxDownloadBytes();
    }

    public function testOperationBudgetRejectsExpiredOperations(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('at least one second');
        (new \App\Services\OperationBudget(0))->tick();
    }
}
