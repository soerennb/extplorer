<?php

namespace Tests\Unit;

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;

class UserModelSecurityTest extends CIUnitTestCase
{
    private string $usersFile;
    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usersFile = config('Storage')->state . '/users.php';
        $this->backup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : null;

        $model = new UserModel();
        $model->saveUsers([[
            'username' => 'alice',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'user',
            'home_dir' => '/',
            'groups' => [],
            'recovery_codes' => ['recovery-1', 'recovery-2'],
        ]]);
    }

    protected function tearDown(): void
    {
        if ($this->backup === null) {
            @unlink($this->usersFile);
        } else {
            file_put_contents($this->usersFile, $this->backup);
        }
        parent::tearDown();
    }

    public function testRepeatedFailuresLockAccountAndSuccessfulLoginResetsCountersAfterUnlock(): void
    {
        $model = new UserModel();

        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($model->verifyUser('alice', 'wrong-password'));
        }

        $locked = $model->getUser('alice');
        $this->assertGreaterThan(time(), (int)$locked['locked_until']);
        $this->assertNull($model->verifyUser('alice', 'correct-password'));

        $this->assertTrue($model->updateUser('alice', ['locked_until' => 0]));
        $this->assertNotNull($model->verifyUser('alice', 'correct-password'));
        $unlocked = $model->getUser('alice');
        $this->assertSame(0, $unlocked['failed_login_count']);
    }

    public function testRecoveryCodeCanOnlyBeConsumedOnce(): void
    {
        $model = new UserModel();

        $this->assertTrue($model->consumeRecoveryCode('alice', 'recovery-1'));
        $this->assertFalse($model->consumeRecoveryCode('alice', 'recovery-1'));
        $this->assertSame(['recovery-2'], $model->getRecoveryCodes('alice'));
    }
}
