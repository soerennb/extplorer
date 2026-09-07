<?php

namespace Tests\Unit;

use App\Controllers\ProfileController;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use OTPHP\TOTP;

class ProfileControllerSecurityTest extends CIUnitTestCase
{
    private string $usersFile;
    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usersFile = config('Storage')->state . '/users.php';
        $this->backup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : null;

        (new UserModel())->saveUsers([[
            'username' => 'alice',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'user',
            'home_dir' => '/',
            'groups' => [],
            '2fa_enabled' => false,
            '2fa_secret' => null,
            'recovery_codes' => [],
        ]]);
        session()->set(['username' => 'alice', 'connection' => ['mode' => 'local']]);
    }

    protected function tearDown(): void
    {
        if ($this->backup === null) {
            @unlink($this->usersFile);
        } else {
            file_put_contents($this->usersFile, $this->backup);
        }
        session()->destroy();
        parent::tearDown();
    }

    public function testTwoFactorEnableUsesServerPendingSecret(): void
    {
        $controller = new ProfileController();
        $controller->initController(Services::request(), Services::response(), Services::logger());

        $setup = json_decode($controller->setup2fa()->getBody(), true);
        $pending = session('pending_2fa');
        $this->assertIsArray($pending);
        $this->assertSame($setup['secret'], $pending['secret']);

        $code = TOTP::create($pending['secret'])->now();
        Services::request()->setBody(json_encode([
            'secret' => 'attacker-controlled-secret',
            'code' => $code,
        ]));
        $response = $controller->enable2fa();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($pending['secret'], (new UserModel())->get2faSecret('alice'));
        $this->assertNull(session('pending_2fa'));
    }

    public function testTwoFactorEnableFailsWithoutUnexpiredPendingSecret(): void
    {
        $controller = new ProfileController();
        $controller->initController(Services::request(), Services::response(), Services::logger());
        Services::request()->setBody(json_encode(['code' => '123456']));

        $response = $controller->enable2fa();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse((bool)(new UserModel())->getUser('alice')['2fa_enabled']);
    }
}
