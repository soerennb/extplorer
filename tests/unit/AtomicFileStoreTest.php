<?php

namespace Tests\Unit;

use App\Services\AtomicFileStore;
use CodeIgniter\Test\CIUnitTestCase;

class AtomicFileStoreTest extends CIUnitTestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = config('Storage')->runtime . '/tests/transaction-state.php';
        @unlink($this->path);
        @unlink($this->path . '.lock');
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        @unlink($this->path . '.lock');
        parent::tearDown();
    }

    public function testTransactionPersistsMutationsAndReturnsCallbackResult(): void
    {
        $result = AtomicFileStore::transaction($this->path, function (array &$state): string {
            $state['counter'] = ((int)($state['counter'] ?? 0)) + 1;
            return 'committed';
        });

        $this->assertSame('committed', $result);
        $this->assertSame(['counter' => 1], AtomicFileStore::read($this->path));
    }

    public function testFailedTransactionDoesNotPersistPartialMutation(): void
    {
        AtomicFileStore::write($this->path, ['counter' => 1]);

        try {
            AtomicFileStore::transaction($this->path, function (array &$state): void {
                $state['counter'] = 2;
                throw new \RuntimeException('abort');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('abort', $exception->getMessage());
        }

        $this->assertSame(['counter' => 1], AtomicFileStore::read($this->path));
    }

    public function testConcurrentTransactionsDoNotLoseUpdates(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is not available.');
        }

        AtomicFileStore::write($this->path, ['counter' => 0]);
        $pid = pcntl_fork();
        $this->assertNotSame(-1, $pid);

        if ($pid === 0) {
            AtomicFileStore::transaction($this->path, function (array &$state): void {
                usleep(100000);
                $state['counter']++;
            });
            exit(0);
        }

        usleep(10000);
        AtomicFileStore::transaction($this->path, function (array &$state): void {
            $state['counter']++;
        });
        pcntl_waitpid($pid, $status);

        $this->assertSame(0, $status);
        $this->assertSame(['counter' => 2], AtomicFileStore::read($this->path));
    }
}
