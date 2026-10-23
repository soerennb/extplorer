<?php

namespace Tests\Unit;

use App\Controllers\UserAdminController;
use App\Models\UserModel;
use App\Services\AuthorizationState;
use App\Services\LogService;
use App\Services\StepUpAuthenticationService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

final class AdminDelegationControllerTest extends CIUnitTestCase
{
    public function testEveryAdministrationMutationUsesTheDelegationBoundary(): void
    {
        $sandbox = new StorageSandbox();
        $logProperty = new \ReflectionProperty(LogService::class, 'filePath');
        $oldLogPath = $logProperty->getValue();
        $logProperty->setValue(null, null);
        try {
            $model = new UserModel();
            $model->saveRoles(['admin' => ['*'], 'manager' => ['read', 'admin_users'], 'settings' => ['read', 'admin_settings']]);
            $model->saveUsers([
                ['username' => 'manager', 'role' => 'manager', 'groups' => [], 'home_dir' => '/team',
                    'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT), 'auth_version' => 1],
                ['username' => 'settings', 'role' => 'settings', 'groups' => [], 'home_dir' => '/team/settings', 'auth_version' => 1],
            ]);
            session()->set(['username' => 'manager', 'permissions' => ['read', 'admin_users'], 'isLoggedIn' => true, 'auth_version' => 1]);
            Services::request()->setHeader('X-Extplorer-Step-Up', (new StepUpAuthenticationService())->issue('user.create', 'correct-password'));
            $before = (new AuthorizationState())->snapshot();
            foreach ([
                ['create', [], ['username' => 'new-admin', 'password' => 'Replacement!2026', 'role' => 'admin', 'home_dir' => '/team/new']],
                ['update', ['settings'], ['password' => 'Replacement!2026']],
                ['delete', ['settings'], []],
                ['saveRole', [], ['name' => 'escalated', 'permissions' => ['read', 'admin_settings']]],
                ['saveGroup', [], ['name' => 'escalated', 'roles' => ['settings']]],
            ] as [$method, $arguments, $body]) {
                Services::resetSingle('response');
                Services::request()->setBody(json_encode((object)$body, JSON_THROW_ON_ERROR));
                $controller = new UserAdminController();
                $controller->initController(Services::request(), Services::response(), Services::logger());
                $response = $controller->$method(...$arguments);
                $this->assertSame(403, $response->getStatusCode(), $method);
                $this->assertSame('admin_delegation_denied', json_decode($response->getBody(), true)['error']);
                $this->assertSame($before, (new AuthorizationState())->snapshot());
            }
        } finally {
            session()->destroy();
            $logProperty->setValue(null, $oldLogPath);
            $sandbox->close();
        }
    }
}
