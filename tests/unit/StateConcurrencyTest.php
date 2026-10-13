<?php

namespace Tests\Unit;

use App\Services\State\DatabaseStateBackend;
use App\Services\State\FileStateBackend;
use App\Services\State\StateBackendInterface;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class StateConcurrencyTest extends CIUnitTestCase
{
    private ?StorageSandbox $sandbox = null;

    protected function tearDown(): void
    {
        $this->sandbox?->close();
        parent::tearDown();
    }

    public static function backends(): iterable
    {
        yield 'file' => ['file'];
        yield 'sql' => ['sql'];
    }

    #[DataProvider('backends')]
    public function testConcurrentWritersAndReadersKeepRelatedDocumentsConsistent(string $driver): void
    {
        if (!function_exists('pcntl_fork')) $this->markTestSkipped('pcntl is required.');
        $this->sandbox = new StorageSandbox();
        $configuration = config('Database')->tests;
        if ($configuration['DBDriver'] === 'SQLite3') {
            $configuration['database'] = $this->sandbox->root . '/concurrency.sqlite';
            $configuration['busyTimeout'] = 10000;
        }
        $configuration['DBPrefix'] = 'audit_' . bin2hex(random_bytes(4)) . '_';
        $warmConnection = $driver === 'sql' ? db_connect($configuration, false) : null;
        $backend = $warmConnection === null ? new FileStateBackend() : new DatabaseStateBackend($warmConnection);
        $a = $this->sandbox->root . '/a.php';
        $b = $this->sandbox->root . '/b.php';
        $defaults = [$a => ['revision' => 0], $b => ['revision' => 0]];
        $backend->transactionMany($defaults, static function (array &$documents): void {});
        $warmConnection?->close();
        unset($backend, $warmConnection);
        foreach (\CodeIgniter\Database\Config::getConnections() as $connection) $connection->close();
        $children = [];
        for ($writer = 0; $writer < 2; $writer++) {
            $pid = pcntl_fork();
            $this->assertNotSame(-1, $pid);
            if ($pid === 0) {
                try {
                    $child = $this->backend($driver, $configuration);
                    for ($index = 0; $index < 30; $index++) {
                        $child->transactionMany($defaults, static function (array &$documents) use ($a, $b): void {
                            $documents[$a]['revision']++;
                            $documents[$b]['revision']++;
                        });
                    }
                    exit(0);
                } catch (\Throwable $error) {
                    file_put_contents($this->sandbox->root . '/error-' . getmypid(), $error->getMessage());
                    exit(1);
                }
            }
            $children[] = $pid;
        }
        // Use a separate connection; a fork must not share a live SQL socket.
        $backend = $this->backend($driver, $configuration);
        for ($index = 0; $index < 40; $index++) {
            $snapshot = $backend->readMany($defaults);
            $this->assertSame($snapshot[$a]['revision'], $snapshot[$b]['revision']);
        }
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            $errors = array_map('file_get_contents', glob($this->sandbox->root . '/error-*') ?: []);
            $this->assertSame(0, pcntl_wexitstatus($status), implode('; ', $errors));
        }
        $this->assertSame(60, $backend->read($a)['revision']);
        $backend->delete($a);
        $backend->delete($b);
        $this->assertFalse($backend->exists($a));
        if ($driver === 'sql') db_connect($configuration, false)->query('DROP TABLE ' . $configuration['DBPrefix'] . 'extplorer_state');
    }

    private function backend(string $driver, array $configuration): StateBackendInterface
    {
        return $driver === 'file' ? new FileStateBackend() : new DatabaseStateBackend(db_connect($configuration, false));
    }
}
