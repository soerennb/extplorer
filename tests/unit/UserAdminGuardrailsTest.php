<?php

namespace Tests\Unit;

use App\Controllers\UserAdminController;
use App\Models\UserModel;
use Config\Services;
use CodeIgniter\Test\CIUnitTestCase;

class UserAdminGuardrailsTest extends CIUnitTestCase
{
    private string $usersFile;
    private string $rolesFile;
    private string $groupsFile;

    private ?string $usersBackup = null;
    private ?string $rolesBackup = null;
    private ?string $groupsBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usersFile = WRITEPATH . 'users.php';
        $this->rolesFile = WRITEPATH . 'roles.php';
        $this->groupsFile = WRITEPATH . 'groups.php';

        $this->usersBackup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : null;
        $this->rolesBackup = is_file($this->rolesFile) ? file_get_contents($this->rolesFile) : null;
        $this->groupsBackup = is_file($this->groupsFile) ? file_get_contents($this->groupsFile) : null;

        session()->set('permissions', ['admin_users']);
        session()->set('username', 'admin');

        $this->seedRbacData();
    }

    protected function tearDown(): void
    {
        $this->restoreFile($this->usersFile, $this->usersBackup);
        $this->restoreFile($this->rolesFile, $this->rolesBackup);
        $this->restoreFile($this->groupsFile, $this->groupsBackup);

        parent::tearDown();
    }

    public function testRoleUsageCapturesDirectAndGroupDependencies(): void
    {
        $model = new UserModel();
        $usage = $model->getRoleUsage('editor');

        $this->assertSame(1, $usage['direct_users_count']);
        $this->assertContains('alice', $usage['direct_users']);

        $this->assertSame(1, $usage['groups_count']);
        $this->assertContains('team', $usage['groups']);

        $this->assertSame(1, $usage['users_via_groups_count']);
        $this->assertContains('bob', $usage['users_via_groups']);
    }

    public function testDeleteRoleBlockedWhenRoleIsInUse(): void
    {
        $controller = new UserAdminController();
        $this->initController($controller);
        $response = $controller->deleteRole('editor');

        $this->assertSame(409, $response->getStatusCode());

        $payload = json_decode($response->getBody(), true);
        $messages = $payload['messages'] ?? [];
        $this->assertSame('blocked', $messages['status'] ?? null);
        $this->assertSame('editor', $messages['details']['role'] ?? null);
    }

    public function testDeleteGroupBlockedWhenGroupIsAssigned(): void
    {
        $controller = new UserAdminController();
        $this->initController($controller);
        $response = $controller->deleteGroup('team');

        $this->assertSame(409, $response->getStatusCode());

        $payload = json_decode($response->getBody(), true);
        $messages = $payload['messages'] ?? [];
        $this->assertSame('blocked', $messages['status'] ?? null);
        $this->assertSame('team', $messages['details']['group'] ?? null);
        $this->assertSame(1, $messages['details']['assigned_users_count'] ?? null);
    }

    public function testDeleteProtectedRoleBlockedEvenWhenUnused(): void
    {
        $model = new UserModel();
        $roles = $model->getRoles();
        $roles['admin'] = ['*', 'admin_settings'];
        $model->saveRoles($roles);

        $controller = new UserAdminController();
        $this->initController($controller);
        $response = $controller->deleteRole('admin');

        $this->assertSame(409, $response->getStatusCode());

        $payload = json_decode($response->getBody(), true);
        $messages = $payload['messages'] ?? [];
        $this->assertSame('blocked', $messages['status'] ?? null);
        $this->assertSame('protected_role', $messages['details']['reason'] ?? null);
    }

    private function seedRbacData(): void
    {
        $model = new UserModel();

        $roles = [
            'admin' => ['*', 'admin_settings'],
            'user' => ['read', 'write'],
            'editor' => ['read', 'write', 'upload'],
        ];

        $groups = [
            'team' => ['editor'],
        ];

        $users = [
            [
                'username' => 'alice',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'editor',
                'home_dir' => '/',
                'groups' => [],
                'allowed_extensions' => '',
                'blocked_extensions' => '',
                '2fa_secret' => null,
                '2fa_enabled' => false,
                'recovery_codes' => [],
            ],
            [
                'username' => 'bob',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'user',
                'home_dir' => '/',
                'groups' => ['team'],
                'allowed_extensions' => '',
                'blocked_extensions' => '',
                '2fa_secret' => null,
                '2fa_enabled' => false,
                'recovery_codes' => [],
            ],
        ];

        $model->saveRoles($roles);
        $model->saveGroups($groups);
        $model->saveUsers($users);
    }

    private function restoreFile(string $path, ?string $contents): void
    {
        if ($contents === null) {
            if (is_file($path)) {
                @unlink($path);
            }
            return;
        }

        file_put_contents($path, $contents);
    }

    private function initController(UserAdminController $controller): void
    {
        $controller->initController(
            Services::request(),
            Services::response(),
            Services::logger()
        );
    }
}
