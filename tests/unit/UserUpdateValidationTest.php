<?php

namespace Tests\Unit;

use App\Controllers\UserAdminController;
use App\Models\UserModel;
use App\Services\AuthenticationService;
use App\Services\StepUpAuthenticationService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

/** Account updates accept the same field types as account creation. */
final class UserUpdateValidationTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        helper('auth');
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([
            ['username' => 'root', 'role' => 'admin', 'home_dir' => '/', 'auth_version' => 1,
                'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT)],
            ['username' => 'vic', 'role' => 'user', 'home_dir' => '/vic', 'auth_version' => 1,
                'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT)],
        ]);
        session()->set(['isLoggedIn' => true, 'username' => 'root', 'permissions' => ['*'], 'auth_version' => 1,
            'connection' => ['mode' => 'local']]);
    }

    protected function tearDown(): void
    {
        session()->destroy();
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testInvalidFieldTypesAreRejectedWithoutChanges(): void
    {
        foreach ([
            ['allowed_extensions' => ['jpg']],
            ['blocked_extensions' => ['exe' => true]],
            ['disabled' => 'false'],
            ['disabled' => 'maybe'],
            ['locked_until' => 'tomorrow'],
            ['failed_login_count' => -1],
        ] as $body) {
            $this->assertSame(400, $this->update('vic', $body), json_encode($body));
        }

        $user = (new UserModel())->getUser('vic');
        $this->assertFalse($user['disabled']);
        $this->assertArrayNotHasKey('allowed_extensions', array_filter($user, static fn($value): bool => is_array($value)));
        $this->assertSame(200, $this->update('vic', ['disabled' => true, 'allowed_extensions' => 'jpg, png']));
        $this->assertTrue((new UserModel())->getUser('vic')['disabled']);
    }

    public function testStoredListPolicyDoesNotBreakSignIn(): void
    {
        $users = (new UserModel())->getUsers();
        $users[1]['allowed_extensions'] = ['jpg', 'png'];
        (new UserModel())->saveUsers($users);

        $user = (new UserModel())->verifyUser('vic', 'correct-password');
        $this->assertTrue((new AuthenticationService())->startLocalSession($user));
        $this->assertSame('jpg,png', session('allowed_extensions'));
    }

    private function update(string $username, array $body): int
    {
        Services::resetSingle('request');
        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', (new StepUpAuthenticationService())->issue('user.update', 'correct-password'));
        $request->setBody(json_encode($body));
        $controller = new UserAdminController();
        $controller->initController($request, Services::response(null, false), Services::logger());
        return $controller->update($username)->getStatusCode();
    }
}
