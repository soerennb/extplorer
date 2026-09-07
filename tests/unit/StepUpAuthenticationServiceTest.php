<?php

namespace Tests\Unit;

use App\Services\StepUpAuthenticationService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class StepUpAuthenticationServiceTest extends CIUnitTestCase
{
    private array $sessionBackup = [];
    private string $usersFile;
    private ?string $usersBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usersFile = config('Storage')->state . '/users.php';
        $this->usersBackup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : null;
        $this->sessionBackup = session()->get();
        session()->set(['isLoggedIn' => true, 'username' => 'stepup-test']);

        $model = new \App\Models\UserModel();
        $roles = $model->getRoles();
        if (!isset($roles['admin'])) $roles['admin'] = ['*'];
        $model->saveRoles($roles);
        $model->saveUsers([[
            'username' => 'stepup-test',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'admin',
            'home_dir' => '/',
            'groups' => [],
            'allowed_extensions' => '',
            'blocked_extensions' => '',
            '2fa_secret' => null,
            '2fa_enabled' => false,
            'recovery_codes' => [],
            'auth_version' => 1,
            'disabled' => false,
        ]]);
    }

    protected function tearDown(): void
    {
        if ($this->usersBackup === null) @unlink($this->usersFile);
        else file_put_contents($this->usersFile, $this->usersBackup);
        $current = session()->get();
        if (is_array($current) && $current !== []) session()->remove(array_keys($current));
        if ($this->sessionBackup !== []) session()->set($this->sessionBackup);
        parent::tearDown();
    }

    public function testProofIsBoundToActionAndCanOnlyBeConsumedOnce(): void
    {
        $service = new StepUpAuthenticationService();
        $token = $service->issue('settings.update', 'correct-password');
        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', $token);

        $this->assertTrue($service->consume($request, 'settings.update'));
        $this->assertFalse($service->consume($request, 'settings.update'));
    }

    public function testWrongPasswordAndActionDoNotIssueUsableProof(): void
    {
        $service = new StepUpAuthenticationService();
        $this->expectException(\RuntimeException::class);
        $service->issue('settings.update', 'wrong-password');
    }
}
