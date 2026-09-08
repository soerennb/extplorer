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

    public function testLegacyInitialAdminWithCustomPasswordIsReleasedOnce(): void
    {
        $model = new UserModel();
        $model->saveUsers([[
            'username' => 'admin',
            'password_hash' => password_hash('chosen-during-setup', PASSWORD_DEFAULT),
            'role' => 'admin',
            'groups' => ['Administrators'],
            'must_change_password' => true,
            'auth_version' => 1,
            'disabled' => false,
        ]]);

        $this->assertSame(1, $model->migrateLegacyInitialAdminPasswordState());
        $user = $model->getUser('admin');
        $this->assertFalse($user['must_change_password']);
        $this->assertSame(2, $user['auth_version']);
        $this->assertTrue(password_verify('chosen-during-setup', $user['password_hash']));
        $this->assertSame(0, $model->migrateLegacyInitialAdminPasswordState());
    }

    public function testLegacyInitialAdminWithLiteralDefaultRemainsRequired(): void
    {
        $model = new UserModel();
        $model->saveUsers([[
            'username' => 'admin',
            'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
            'role' => 'admin',
            'groups' => ['Administrators'],
            'must_change_password' => true,
            'auth_version' => 1,
            'disabled' => false,
        ]]);

        $this->assertSame(0, $model->migrateLegacyInitialAdminPasswordState());
        $user = $model->getUser('admin');
        $this->assertTrue($user['must_change_password']);
        $this->assertSame(1, $user['auth_version']);
    }

    public function testLegacyInitialAdminIsNotReleasedWhenAnotherActiveAdminExists(): void
    {
        $model = new UserModel();
        $model->saveUsers([
            [
                'username' => 'admin',
                'password_hash' => password_hash('chosen-during-setup', PASSWORD_DEFAULT),
                'role' => 'admin',
                'groups' => ['Administrators'],
                'must_change_password' => true,
                'auth_version' => 1,
                'disabled' => false,
            ],
            [
                'username' => 'operator',
                'password_hash' => password_hash('operator-password', PASSWORD_DEFAULT),
                'role' => 'admin',
                'groups' => ['Administrators'],
                'must_change_password' => false,
                'auth_version' => 1,
                'disabled' => false,
            ],
        ]);

        $this->assertSame(0, $model->migrateLegacyInitialAdminPasswordState());
        $this->assertTrue($model->getUser('admin')['must_change_password']);
    }
}
