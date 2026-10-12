<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\AtomicFileStore;
use App\Services\AuthorizationState;
use App\Services\AuthenticationService;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class StateTransactionsTest extends CIUnitTestCase
{
    private ?StorageSandbox $sandbox = null;

    protected function tearDown(): void
    {
        session()->destroy();
        $this->sandbox?->close();
        parent::tearDown();
    }

    public static function backends(): iterable
    {
        yield 'file' => ['file'];
        yield 'sqlite' => ['sqlite'];
    }

    #[DataProvider('backends')]
    public function testRelatedDocumentsCommitTogetherAndRollbackOnFailure(string $driver): void
    {
        $this->sandbox = new StorageSandbox($driver);
        $a = config('Storage')->state . '/a.php';
        $b = config('Storage')->state . '/b.php';
        $defaults = [$a => ['version' => 1], $b => ['version' => 1]];
        AtomicFileStore::transactionMany($defaults, function (array &$state) use ($a, $b): void {
            $state[$a]['version'] = $state[$b]['version'] = 2;
        });
        try {
            AtomicFileStore::transactionMany($defaults, function (array &$state) use ($a): void {
                $state[$a]['version'] = 3;
                throw new \RuntimeException('abort');
            });
            $this->fail('Expected transaction failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('abort', $exception->getMessage());
        }
        $this->assertSame([$a => ['version' => 2], $b => ['version' => 2]], AtomicFileStore::readMany($defaults));
    }

    public function testInterruptedFileCommitIsRecoveredBeforeAnyRead(): void
    {
        $this->sandbox = new StorageSandbox();
        $directory = config('Storage')->state;
        $header = '<?php die("Access denied"); ?>' . PHP_EOL;
        file_put_contents($directory . '/a.php', $header . '{"version":2}');
        file_put_contents($directory . '/b.php', $header . '{"version":1}');
        file_put_contents($directory . '/.extplorer-state-journal.php', $header . json_encode([
            'a.php' => ['version' => 1], 'b.php' => ['version' => 1], 'new.php' => null,
        ]));
        file_put_contents($directory . '/new.php', $header . '{"version":2}');
        $this->assertSame(['version' => 1], AtomicFileStore::read($directory . '/a.php'));
        $this->assertSame(['version' => 1], AtomicFileStore::read($directory . '/b.php'));
        $this->assertFalse(AtomicFileStore::exists($directory . '/new.php'));
        $this->assertFileDoesNotExist($directory . '/.extplorer-state-journal.php');
    }

    #[DataProvider('backends')]
    public function testPolicyMutationInvalidatesAffectedUsersAndRejectsStaleSession(string $driver): void
    {
        $this->sandbox = new StorageSandbox($driver);
        $model = new UserModel();
        $model->saveUsers([['username' => 'alice', 'role' => 'user', 'groups' => [], 'auth_version' => 1,
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT)]]);
        $before = $model->getUser('alice');
        $model->saveRole('custom', ['read']);
        $model->saveRole('user', ['read']);
        $snapshot = $model->authorizationSnapshot('alice');
        $this->assertSame(2, $snapshot['user']['auth_version']);
        $this->assertSame(['read'], $snapshot['permissions']);
        $this->assertFalse((new AuthenticationService($model))->startLocalSession($before));
        $this->assertTrue((new AuthenticationService($model))->startLocalSession($snapshot['user']));
        $this->assertSame(['read'], session('permissions'));
        $this->assertArrayHasKey('custom', $model->getRoles());
    }
}
