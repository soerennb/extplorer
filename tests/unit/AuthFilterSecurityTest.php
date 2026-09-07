<?php

namespace Tests\Unit;

use App\Filters\AuthFilter;
use App\Models\UserModel;
use App\Services\RememberMeService;
use App\Services\SettingsService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class AuthFilterSecurityTest extends CIUnitTestCase
{
    private string $usersFile;
    private ?string $usersBackup = null;
    private array $settingsBackup = [];
    private string $tokensFile;
    private ?string $tokensBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $storage = config('Storage');
        $this->usersFile = $storage->state . '/users.php';
        $this->tokensFile = $storage->state . '/remember_tokens.php';
        $this->usersBackup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : null;
        $this->tokensBackup = is_file($this->tokensFile) ? file_get_contents($this->tokensFile) : null;
        $this->settingsBackup = (new SettingsService())->getSettings();

        (new UserModel())->saveUsers([[
            'username' => 'alice',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'user',
            'home_dir' => '/',
        ]]);
        (new SettingsService())->saveSettings(['session_idle_timeout_minutes' => 1]);
        session()->set([
            'isLoggedIn' => true,
            'username' => 'alice',
            'auth_version' => 1,
            'connection' => ['mode' => 'local'],
            'last_activity_ts' => time() - 120,
        ]);
        unset($_COOKIE[RememberMeService::COOKIE_NAME]);
        if (is_file($this->tokensFile)) {
            unlink($this->tokensFile);
        }
    }

    protected function tearDown(): void
    {
        if ($this->usersBackup === null) {
            @unlink($this->usersFile);
        } else {
            file_put_contents($this->usersFile, $this->usersBackup);
        }
        (new SettingsService())->saveSettings($this->settingsBackup);
        if ($this->tokensBackup === null) {
            @unlink($this->tokensFile);
        } else {
            file_put_contents($this->tokensFile, $this->tokensBackup);
        }
        @unlink($this->tokensFile . '.lock');
        unset($_COOKIE[RememberMeService::COOKIE_NAME]);
        session()->destroy();
        parent::tearDown();
    }

    public function testIdleTimeoutDoesNotRestoreRememberedSession(): void
    {
        $remember = new RememberMeService(new UserModel(), $this->tokensFile);
        $rememberResponse = Services::response();
        $remember->remember('alice', $rememberResponse);
        $_COOKIE[RememberMeService::COOKIE_NAME] = $rememberResponse->getCookie(RememberMeService::COOKIE_NAME)->getValue();

        $response = (new AuthFilter())->before(Services::request());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse((bool)session('isLoggedIn'));
    }
}
