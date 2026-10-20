<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\AdminDelegationException;
use App\Services\AuthorizationState;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class AdminDelegationSecurityTest extends CIUnitTestCase
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
    public function testDelegatedAdministratorsCannotEscalateOrTakeOverStrongerAccounts(string $driver): void
    {
        $model = $this->seed($driver);
        $before = (new AuthorizationState())->snapshot();
        $attacks = [
            fn() => $model->saveRole('viewer', ['read', 'admin_settings'], 'manager'),
            fn() => $model->saveRole('settings', ['read'], 'manager'),
            fn() => $model->saveRole('admin', ['*', 'admin_settings'], 'manager'),
            fn() => $model->saveGroup('team', ['admin'], 'manager'),
            fn() => $model->saveGroup('new-privileged-group', ['settings'], 'manager'),
            fn() => $model->updateUser('manager', ['role' => 'admin'], 'manager'),
            fn() => $model->updateUser('manager', ['groups' => ['admins']], 'manager'),
            fn() => $model->updateUser('operator', ['password' => 'Replacement!2026'], 'manager'),
            fn() => $model->updateUser('operator', ['role' => 'viewer'], 'manager'),
            fn() => $model->deleteUser('operator', 'manager'),
            fn() => $model->addUser('new-admin', 'Replacement!2026', 'admin', '/team', [], '', '', false, 'manager'),
            fn() => $model->updateUser('teammate', ['home_dir' => '/other-team'], 'manager'),
            fn() => $model->addUser('outside', 'Replacement!2026', 'viewer', '/', [], '', '', false, 'manager'),
        ];
        // A forged/stale session cannot override the persisted authority.
        session()->set(['username' => 'manager', 'permissions' => ['*']]);
        foreach ($attacks as $index => $attack) {
            try {
                $attack();
                $this->fail('Expected a delegation denial for variant ' . $index);
            } catch (AdminDelegationException $error) {
                $this->assertSame(403, $error->getCode());
            }
            $this->assertSame($before, (new AuthorizationState())->snapshot(), 'Denied mutations must leave all authorization documents unchanged.');
        }
    }

    #[DataProvider('backends')]
    public function testOrdinaryDelegatedManagementAndFullAdministrationRemainAvailable(string $driver): void
    {
        $model = $this->seed($driver);
        $this->assertTrue($model->addUser('new-member', 'Replacement!2026', 'viewer', '/team/new', [], '', '', false, 'manager'));
        $model->saveRole('editor', ['read', 'write'], 'manager');
        $model->saveGroup('editors', ['editor'], 'manager');
        $this->assertTrue($model->updateUser('new-member', ['groups' => ['editors']], 'manager'));
        $this->assertSame(['read', 'write'], $model->getPermissions('new-member'));
        $this->assertTrue($model->deleteUser('new-member', 'manager'));
        $model->deleteGroup('editors', 'manager');
        $model->deleteRole('editor', 'manager');
        $this->assertTrue($model->updateUser('teammate', ['role' => 'admin'], 'operator'));
        $this->assertSame(['*'], $model->getPermissions('teammate'));
    }

    #[DataProvider('backends')]
    public function testSharedRoleCannotModifyUsersOutsideTheDelegatedHome(string $driver): void
    {
        $model = $this->seed($driver);
        $model->addUser('outsider', 'Replacement!2026', 'viewer', '/other-team');
        $this->expectException(AdminDelegationException::class);
        $model->saveRole('viewer', ['read', 'write'], 'manager');
    }

    #[DataProvider('backends')]
    public function testRevokedOrDisabledActorCannotUseEarlierRights(string $driver): void
    {
        $model = $this->seed($driver);
        $model->updateUser('manager', ['disabled' => true]);
        $this->expectException(AdminDelegationException::class);
        $model->updateUser('teammate', ['password' => 'Replacement!2026'], 'manager');
    }

    #[DataProvider('backends')]
    public function testExtensionRestrictionsCannotBeBypassedThroughDelegation(string $driver): void
    {
        $model = $this->seed($driver);
        $model->updateUser('manager', ['allowed_extensions' => '.TXT, jpg', 'blocked_extensions' => 'jpg']);
        $before = (new AuthorizationState())->snapshot();
        foreach ([
            fn() => $model->updateUser('manager', ['allowed_extensions' => ''], 'manager'),
            fn() => $model->updateUser('manager', ['blocked_extensions' => ''], 'manager'),
            fn() => $model->addUser('unrestricted', 'Replacement!2026', 'viewer', '/team', [], '', '', false, 'manager'),
            fn() => $model->updateUser('teammate', ['password' => 'Replacement!2026'], 'manager'),
        ] as $attack) {
            try { $attack(); $this->fail('Expected extension-policy denial.'); }
            catch (AdminDelegationException) {}
            $this->assertSame($before, (new AuthorizationState())->snapshot());
        }
        $this->assertTrue($model->addUser('restricted', 'Replacement!2026', 'viewer', '/team', [], 'txt', '', false, 'manager'));
        $model->updateUser('manager', ['allowed_extensions' => '', 'blocked_extensions' => 'zip; pdf']);
        $this->assertTrue($model->addUser('restricted-two', 'Replacement!2026', 'viewer', '/team', [], '', '.pdf ZIP jpg', false, 'manager'));
        $this->expectException(AdminDelegationException::class);
        $model->updateUser('restricted-two', ['blocked_extensions' => 'pdf'], 'manager');
    }

    private function seed(string $driver): UserModel
    {
        $this->sandbox = new StorageSandbox($driver);
        $model = new UserModel();
        $model->saveRoles(['admin' => ['*'], 'manager' => ['admin_users', 'read', 'write'],
            'viewer' => ['read'], 'settings' => ['read', 'admin_settings']]);
        $model->saveGroups(['admins' => ['admin'], 'team' => ['viewer']]);
        $model->saveUsers([
            ['username' => 'operator', 'role' => 'admin', 'home_dir' => '/', 'groups' => [], 'auth_version' => 1],
            ['username' => 'manager', 'role' => 'manager', 'home_dir' => '/team', 'groups' => [], 'auth_version' => 1],
            ['username' => 'teammate', 'role' => 'viewer', 'home_dir' => '/team/member', 'groups' => [], 'auth_version' => 1],
        ]);
        return $model;
    }
}
