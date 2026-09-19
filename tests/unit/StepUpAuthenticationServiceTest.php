<?php

namespace Tests\Unit;

use App\Services\AuthenticationService;
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
        session()->set([
            'isLoggedIn' => true,
            'username' => 'stepup-test',
            'auth_version' => 1,
        ]);
        StepUpAuthenticationService::clearGrant();

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

    public function testGrantCanBeReusedAcrossSensitiveActionsWithinTheGracePeriod(): void
    {
        $service = new StepUpAuthenticationService();
        $token = $service->issue('settings.update', 'correct-password');
        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', $token);

        $this->assertTrue($service->consume($request, 'settings.update'));
        $this->assertTrue($service->consume($request, 'user.delete'));

        $request->setHeader('X-Extplorer-Step-Up', '');
        $this->assertTrue($service->consume($request, 'mount.test'));
    }

    public function testExpiredGrantIsRemovedAndCannotAuthorizeAnAction(): void
    {
        $service = new StepUpAuthenticationService();
        $service->issue('settings.update', 'correct-password');
        $grant = session('step_up_grant');
        $this->assertIsArray($grant);
        $grant['expires_at'] = time() - 1;
        session()->set('step_up_grant', $grant);

        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', '');

        $this->assertFalse($service->consume($request, 'settings.update'));
        $this->assertNull(session('step_up_grant'));
    }

    public function testInvalidHeaderDoesNotAuthorizeButLeavesTheActiveGrantUsable(): void
    {
        $service = new StepUpAuthenticationService();
        $service->issue('settings.update', 'correct-password');
        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', str_repeat('a', 64));

        $this->assertFalse($service->consume($request, 'settings.update'));
        $request->setHeader('X-Extplorer-Step-Up', '');
        $this->assertTrue($service->consume($request, 'settings.update'));
    }

    public function testGrantIsInvalidatedByAuthVersionAndDisabledStateChanges(): void
    {
        $service = new StepUpAuthenticationService();
        $service->issue('settings.update', 'correct-password');
        (new \App\Models\UserModel())->bumpAuthVersion('stepup-test');

        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', '');
        $this->assertFalse($service->consume($request, 'settings.update'));

        $model = new \App\Models\UserModel();
        $model->saveUsers([[
            'username' => 'stepup-test',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'user',
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
        session()->set('auth_version', 1);
        $service->issue('settings.update', 'correct-password');
        $model->updateUser('stepup-test', ['disabled' => true]);

        $this->assertFalse($service->consume($request, 'settings.update'));
    }

    public function testNewGrantInvalidatesThePreviousCompatibilityToken(): void
    {
        $service = new StepUpAuthenticationService();
        $oldToken = $service->issue('settings.update', 'correct-password');
        $service->issue('user.update', 'correct-password');

        $request = Services::request();
        $request->setHeader('X-Extplorer-Step-Up', $oldToken);
        $this->assertFalse($service->consume($request, 'settings.update'));
        $request->setHeader('X-Extplorer-Step-Up', '');
        $this->assertTrue($service->consume($request, 'settings.update'));
    }

    public function testAuthenticationSessionStartClearsAnExistingGrant(): void
    {
        $service = new StepUpAuthenticationService();
        $service->issue('settings.update', 'correct-password');
        $user = (new \App\Models\UserModel())->getUser('stepup-test');
        $this->assertIsArray($user);

        (new AuthenticationService(new \App\Models\UserModel()))->startLocalSession($user);

        $this->assertNull(session('step_up_grant'));
    }

    public function testWrongPasswordAndActionDoNotIssueUsableProof(): void
    {
        $service = new StepUpAuthenticationService();
        try {
            $service->issue('settings.update', 'wrong-password');
            $this->fail('Wrong password unexpectedly issued a step-up grant.');
        } catch (\RuntimeException) {
            $this->assertNull(session('step_up_grant'));
        }

        $this->expectException(\RuntimeException::class);
        $service->issue('unsupported.action', 'correct-password');
    }

    public function testTwoFactorAccountRequiresCurrentTotpForStepUp(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $model = new \App\Models\UserModel();
        $model->updateUser('stepup-test', ['2fa_secret' => $secret, '2fa_enabled' => true]);
        session()->set('auth_version', (int)$model->getUser('stepup-test')['auth_version']);
        $service = new StepUpAuthenticationService($model);

        try {
            $service->issue('webdav-credential.create', 'correct-password');
            $this->fail('A password-only step-up was accepted for a 2FA account.');
        } catch (\RuntimeException) {
            $this->assertNull(session('step_up_grant'));
        }

        $code = \OTPHP\TOTP::create($secret)->now();
        $this->assertNotSame('', $service->issue('webdav-credential.create', 'correct-password', $code));
    }
}
