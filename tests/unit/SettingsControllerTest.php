<?php

namespace Tests\Unit;

use App\Controllers\SettingsController;
use App\Models\UserModel;
use App\Services\SettingsService;
use App\Services\StepUpAuthenticationService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class SettingsControllerTest extends CIUnitTestCase
{
    private string $settingsFile;
    private string $usersFile;
    private ?string $settingsBackup = null;
    private ?string $usersBackup = null;
    private array $sessionBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $storage = config('Storage');
        $this->settingsFile = $storage->state . '/settings.php';
        $this->usersFile = $storage->state . '/users.php';
        $this->settingsBackup = is_file($this->settingsFile) ? file_get_contents($this->settingsFile) : null;
        $this->usersBackup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : null;
        $this->sessionBackup = session()->get();

        session()->set([
            'permissions' => ['admin_settings'],
            'username' => 'settings-admin',
            'isLoggedIn' => true,
        ]);

        (new UserModel())->saveUsers([[
            'username' => 'settings-admin',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'admin',
            'home_dir' => '/',
            'groups' => [],
            'allowed_extensions' => '',
            'blocked_extensions' => '',
            '2fa_enabled' => false,
            '2fa_secret' => null,
            'recovery_codes' => [],
            'auth_version' => 1,
            'disabled' => false,
        ]]);
    }

    protected function tearDown(): void
    {
        $this->restoreFile($this->settingsFile, $this->settingsBackup);
        $this->restoreFile($this->usersFile, $this->usersBackup);

        $currentSession = session()->get();
        if (is_array($currentSession) && $currentSession !== []) {
            session()->remove(array_keys($currentSession));
        }
        if ($this->sessionBackup !== []) {
            session()->set($this->sessionBackup);
        }

        parent::tearDown();
    }

    public function testIndexReturnsOnlyNormalizedRemoteEndpoints(): void
    {
        (new SettingsService())->saveSettings([
            'remote_endpoint_allowlist' => [
                'sftp://files.example.com:22',
                'legacy-host:22',
            ],
        ]);

        $controller = new SettingsController();
        $this->initController($controller);
        $payload = json_decode($controller->index()->getBody(), true);

        $this->assertSame([
            ['protocol' => 'sftp', 'host' => 'files.example.com', 'port' => 22],
        ], $payload['remote_endpoint_allowlist']);
        $this->assertSame('sftp://files.example.com:22', $payload['remote_endpoint_allowlist_text']);
    }

    public function testEmptyEndpointTextClearsInvalidRawLegacyValue(): void
    {
        (new SettingsService())->saveSettings([
            'remote_endpoint_allowlist' => ['legacy-host:22'],
        ]);

        $this->authorizeStepUp();
        Services::request()->setBody(json_encode([
            'default_transfer_expiry' => 7,
            'remote_endpoint_allowlist' => ['legacy-host:22'],
            'remote_endpoint_allowlist_text' => '',
        ], JSON_THROW_ON_ERROR));

        $controller = new SettingsController();
        $this->initController($controller);
        $response = $controller->update();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], (new SettingsService())->get('remote_endpoint_allowlist'));
    }

    public function testWhitespaceOnlyEndpointTextClearsAllowlist(): void
    {
        $this->authorizeStepUp();
        Services::request()->setBody(json_encode([
            'remote_endpoint_allowlist_text' => "\n  \r\n",
        ], JSON_THROW_ON_ERROR));

        $controller = new SettingsController();
        $this->initController($controller);
        $response = $controller->update();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], (new SettingsService())->get('remote_endpoint_allowlist'));
    }

    public function testInvalidEndpointReturnsStableLocalizedErrorCode(): void
    {
        $this->authorizeStepUp();
        Services::request()->setBody(json_encode([
            'remote_endpoint_allowlist_text' => 'https://example.com:443',
        ], JSON_THROW_ON_ERROR));

        $controller = new SettingsController();
        $this->initController($controller);
        $response = $controller->update();
        $payload = json_decode($response->getBody(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('invalid_remote_endpoint_allowlist', $payload['error']);
        $this->assertSame(
            'Invalid remote endpoint allowlist entry. Use protocol://server:port.',
            $payload['messages']['error']
        );
    }

    private function initController(SettingsController $controller): void
    {
        $controller->initController(
            Services::request(),
            Services::response(),
            Services::logger()
        );
    }

    private function authorizeStepUp(): void
    {
        $token = (new StepUpAuthenticationService())->issue('settings.update', 'correct-password');
        Services::request()->setHeader('X-Extplorer-Step-Up', $token);
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
}
