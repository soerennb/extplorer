<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\InstallStateService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class InstallStateServiceTest extends CIUnitTestCase
{
    /** @var array<string, string|null> */
    private array $environment = [];

    /** @var array<string, string|null> */
    private array $backups = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['EXTPLORER_ADMIN_USER', 'EXTPLORER_ADMIN_PASS', 'EXTPLORER_ADMIN_PASSWORD_FILE'] as $key) {
            $value = getenv($key);
            $this->environment[$key] = $value === false ? null : $value;
            putenv($key);
        }

        $storage = config('Storage');
        foreach ([
            $storage->state . '/users.php',
            $storage->state . '/roles.php',
            $storage->state . '/groups.php',
            $storage->root . '/installed.lock',
            $storage->root . '/.extplorer-install-token',
            $storage->root . '/.install.lock',
        ] as $path) {
            $this->backups[$path] = is_file($path) ? file_get_contents($path) : null;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->backups as $path => $content) {
            if ($content === null) {
                if (is_file($path)) {
                    unlink($path);
                }
                continue;
            }

            file_put_contents($path, $content);
        }

        foreach ($this->environment as $key => $value) {
            if ($value === null) {
                putenv($key);
            } else {
                putenv("{$key}={$value}");
            }
        }

        parent::tearDown();
    }

    public function testFreshInstallationRequiresAOneTimeToken(): void
    {
        $service = new InstallStateService();
        $service->ensureClaimToken();

        $tokenPath = config('Storage')->root . '/.extplorer-install-token';
        $token = trim((string)file_get_contents($tokenPath));

        $this->assertSame(InstallStateService::STATUS_CLAIMABLE, $service->status());
        $this->assertSame(64, strlen($token));
        $this->assertSame(0600, fileperms($tokenPath) & 0777);

        $this->expectException(RuntimeException::class);
        $service->claim('wrong-token', 'operator', 'first-password');
    }

    public function testClaimCreatesAdminConsumesTokenAndPreventsSecondClaim(): void
    {
        $service = new InstallStateService();
        $service->ensureClaimToken();
        $tokenPath = config('Storage')->root . '/.extplorer-install-token';
        $token = trim((string)file_get_contents($tokenPath));

        $result = $service->claim($token, 'operator', 'first-password');
        $model = new UserModel();

        $this->assertSame(['username' => 'operator'], $result);
        $this->assertSame(InstallStateService::STATUS_INSTALLED, $service->status());
        $this->assertFileDoesNotExist($tokenPath);
        $this->assertTrue($model->getUser('operator')['must_change_password']);
        $this->assertNotContains('mount_external', $model->getRoles()['user']);

        $this->expectException(RuntimeException::class);
        $service->claim($token, 'another-admin', 'another-password');
    }

    public function testExistingUsersWithoutMarkerAreRepairStateAndCannotBeClaimed(): void
    {
        $model = new UserModel();
        $model->saveRoles(['admin' => ['*'], 'user' => ['read']]);
        $model->addUser('operator', 'first-password', 'admin');

        $service = new InstallStateService();

        $this->assertSame(InstallStateService::STATUS_REPAIR, $service->status());
        $this->expectException(RuntimeException::class);
        $service->claim('anything', 'another-admin', 'another-password');
    }
}
