<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\DavAuthenticationService;
use App\Services\WebDavCredentialService;
use CodeIgniter\Test\CIUnitTestCase;

final class DavAuthenticationServiceTest extends CIUnitTestCase
{
    private string $usersPath;
    private string $credentialsPath;
    private string|false $usersBackup;
    private string|false $credentialsBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $state = config('Storage')->state;
        $this->usersPath = $state . '/users.php';
        $this->credentialsPath = $state . '/webdav_credentials.php';
        $this->usersBackup = is_file($this->usersPath) ? file_get_contents($this->usersPath) : false;
        $this->credentialsBackup = is_file($this->credentialsPath) ? file_get_contents($this->credentialsPath) : false;
        (new UserModel())->saveUsers([[
            'username' => 'dav-user',
            'password_hash' => password_hash('account-password', PASSWORD_DEFAULT),
            'role' => 'user',
            'home_dir' => '/',
            'groups' => [],
            'allowed_extensions' => '',
            'blocked_extensions' => '',
            '2fa_secret' => null,
            '2fa_enabled' => true,
            'recovery_codes' => [],
            'auth_version' => 1,
            'disabled' => false,
            'locked_until' => 0,
        ]]);
        @unlink($this->credentialsPath);
    }

    protected function tearDown(): void
    {
        $this->restore($this->usersPath, $this->usersBackup);
        $this->restore($this->credentialsPath, $this->credentialsBackup);
        @unlink($this->credentialsPath . '.lock');
        parent::tearDown();
    }

    public function testTwoFactorAccountRejectsAccountPasswordAndAcceptsAppPassword(): void
    {
        $credentials = new WebDavCredentialService();
        $secret = $credentials->create('dav-user', 'Client')['secret'];
        $service = new DavAuthenticationService(new UserModel(), $credentials);

        $this->assertNull($service->authenticate('dav-user', 'account-password'));
        $this->assertSame('dav-user', $service->authenticate('dav-user', $secret)['username']);
    }

    private function restore(string $path, string|false $contents): void
    {
        if ($contents === false) @unlink($path);
        else file_put_contents($path, $contents);
    }
}
