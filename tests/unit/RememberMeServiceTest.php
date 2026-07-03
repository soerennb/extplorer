<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\RememberMeService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class RememberMeServiceTest extends CIUnitTestCase
{
    private string $usersFile;
    private string $tokensFile;
    private ?string $usersBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usersFile = WRITEPATH . 'users.php';
        $this->tokensFile = WRITEPATH . 'test_remember_tokens.php';
        $this->usersBackup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : null;

        if (is_file($this->tokensFile)) {
            unlink($this->tokensFile);
        }

        $model = new UserModel();
        $model->saveUsers([
            [
                'username' => 'alice',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'user',
                'home_dir' => '/',
                'groups' => [],
                'allowed_extensions' => '',
                'blocked_extensions' => '',
                '2fa_secret' => null,
                '2fa_enabled' => false,
                'recovery_codes' => [],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->usersBackup === null) {
            if (is_file($this->usersFile)) {
                unlink($this->usersFile);
            }
        } else {
            file_put_contents($this->usersFile, $this->usersBackup);
        }

        if (is_file($this->tokensFile)) {
            unlink($this->tokensFile);
        }

        unset($_COOKIE[RememberMeService::COOKIE_NAME]);
        session()->destroy();

        parent::tearDown();
    }

    public function testRememberStoresOnlyValidatorHash(): void
    {
        $service = new RememberMeService(new UserModel(), $this->tokensFile);
        $response = Services::response();

        $service->remember('alice', $response);

        $cookie = $response->getCookie(RememberMeService::COOKIE_NAME);
        $this->assertNotNull($cookie);
        [$selector, $validator] = explode(':', $cookie->getValue(), 2);

        $tokens = $this->readTokens();
        $this->assertArrayHasKey($selector, $tokens);
        $this->assertSame('alice', $tokens[$selector]['username']);
        $this->assertNotSame($validator, $tokens[$selector]['validator_hash']);
        $this->assertSame(hash('sha256', $validator), $tokens[$selector]['validator_hash']);
    }

    public function testRestoreStartsLocalSessionAndRotatesToken(): void
    {
        $service = new RememberMeService(new UserModel(), $this->tokensFile);
        $firstResponse = Services::response();
        $service->remember('alice', $firstResponse);
        $originalCookie = $firstResponse->getCookie(RememberMeService::COOKIE_NAME)->getValue();
        [$originalSelector] = explode(':', $originalCookie, 2);

        $_COOKIE[RememberMeService::COOKIE_NAME] = $originalCookie;
        $secondResponse = Services::response();
        $this->assertTrue($service->restore(Services::request(), $secondResponse));

        $this->assertTrue((bool)session('isLoggedIn'));
        $this->assertSame('alice', session('username'));
        $this->assertSame(['mode' => 'local'], session('connection'));

        $tokens = $this->readTokens();
        $this->assertArrayNotHasKey($originalSelector, $tokens);
        $this->assertNotSame($originalCookie, $secondResponse->getCookie(RememberMeService::COOKIE_NAME)->getValue());
    }

    public function testInvalidValidatorClearsStoredToken(): void
    {
        $service = new RememberMeService(new UserModel(), $this->tokensFile);
        $response = Services::response();
        $service->remember('alice', $response);
        $cookie = $response->getCookie(RememberMeService::COOKIE_NAME)->getValue();
        [$selector] = explode(':', $cookie, 2);

        $_COOKIE[RememberMeService::COOKIE_NAME] = $selector . ':bad-validator';
        $this->assertFalse($service->restore(Services::request(), Services::response()));

        $this->assertArrayNotHasKey($selector, $this->readTokens());
    }

    private function readTokens(): array
    {
        $content = file_get_contents($this->tokensFile);
        $json = substr($content, (int)strpos($content, "\n") + 1);
        return json_decode($json, true) ?? [];
    }
}
