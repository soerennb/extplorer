<?php

use App\Services\State\DatabaseStateBackend;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class StateBackendTest extends CIUnitTestCase
{
    private ?\CodeIgniter\Database\BaseConnection $connection = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = db_connect('tests');
        $this->connection->query('DROP TABLE IF EXISTS ' . $this->connection->escapeIdentifiers($this->connection->prefixTable('extplorer_state')));
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            $this->connection->query('DROP TABLE IF EXISTS ' . $this->connection->escapeIdentifiers($this->connection->prefixTable('extplorer_state')));
            $this->connection->close();
        }
        parent::tearDown();
    }

    public function testSqlStateBackendPersistsAndTransactionsAreAtomic(): void
    {
        $backend = new DatabaseStateBackend($this->connection);
        $path = '/tmp/extplorer-state-' . bin2hex(random_bytes(4));

        $backend->write($path, ['counter' => 1]);
        $result = $backend->transaction($path, function (array &$state): string {
            $state['counter']++;
            return 'committed';
        });

        $this->assertSame('committed', $result);
        $this->assertSame(['counter' => 2], $backend->read($path));
        $this->assertTrue($backend->exists($path));

        try {
            $backend->transaction($path, function (array &$state): void {
                $state['counter'] = 99;
                throw new RuntimeException('abort');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('abort', $exception->getMessage());
        }

        $this->assertSame(['counter' => 2], $backend->read($path));
        $backend->verify();
    }
}
