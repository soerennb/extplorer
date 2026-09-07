<?php

namespace Tests\Unit;

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;

class PasswordStateMigrationTest extends CIUnitTestCase
{
    private string $usersFile;
    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usersFile = config('Storage')->state . '/users.php';
        $this->backup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : null;
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

    public function testLegacyDefaultIsMarkedOnlyDuringMigration(): void
    {
        $model = new UserModel();
        $model->saveUsers([
            [
                'username' => 'admin',
                'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
                'role' => 'admin',
            ],
            [
                'username' => 'operator',
                'password_hash' => password_hash('different-password', PASSWORD_DEFAULT),
                'role' => 'admin',
            ],
        ]);

        $this->assertSame(2, $model->migratePasswordState());
        $this->assertTrue($model->getUser('admin')['must_change_password']);
        $this->assertFalse($model->getUser('operator')['must_change_password']);
        $this->assertSame(1, $model->getUser('admin')['auth_version']);
        $this->assertFalse($model->getUser('admin')['disabled']);
        $this->assertSame(0, $model->getUser('admin')['failed_login_count']);
        $this->assertSame(0, $model->migratePasswordState());
    }
}
