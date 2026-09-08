<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\AdminBootstrapService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class AdminBootstrapServiceTest extends CIUnitTestCase
{
    /** @var array<string, string|null> */
    private array $environment = [];

    /** @var array<string, string|null> */
    private array $backups = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'EXTPLORER_ADMIN_USER',
            'EXTPLORER_ADMIN_PASS',
            'EXTPLORER_ADMIN_PASSWORD_FILE',
            'EXTPLORER_ADMIN_RESET_PASSWORD',
        ] as $key) {
            $value = getenv($key);
            $this->environment[$key] = $value === false ? null : $value;
            putenv($key);
        }

        $storage = config('Storage');
        foreach ([
            $storage->state . '/users.php',
            $storage->state . '/roles.php',
            $storage->state . '/groups.php',
            $storage->state . '/admin-bootstrap.php',
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

    public function testFreshStorageBootstrapsConfiguredAdministrator(): void
    {
        putenv('EXTPLORER_ADMIN_USER=operator');
        putenv('EXTPLORER_ADMIN_PASS=first-password');

        $message = (new AdminBootstrapService())->bootstrapFromEnvironment();
        $model = new UserModel();

        $this->assertSame('Administrator bootstrap completed.', $message);
        $this->assertNotNull($model->verifyUser('operator', 'first-password'));
        $this->assertFalse($model->getUser('operator')['must_change_password']);
        $this->assertNotContains('mount_external', $model->getRoles()['user']);
        $this->assertFileExists(config('Storage')->root . '/installed.lock');
    }

    public function testExistingStorageDoesNotOverwritePasswordWithoutReset(): void
    {
        putenv('EXTPLORER_ADMIN_PASS=first-password');
        (new AdminBootstrapService())->bootstrapFromEnvironment();

        putenv('EXTPLORER_ADMIN_PASS=second-password');
        (new AdminBootstrapService())->bootstrapFromEnvironment();

        $model = new UserModel();
        $this->assertNotNull($model->verifyUser('admin', 'first-password'));
        $this->assertNull($model->verifyUser('admin', 'second-password'));
    }

    public function testResetChangesPasswordOnlyForExplicitRequest(): void
    {
        putenv('EXTPLORER_ADMIN_PASS=first-password');
        (new AdminBootstrapService())->bootstrapFromEnvironment();

        putenv('EXTPLORER_ADMIN_PASS=second-password');
        putenv('EXTPLORER_ADMIN_RESET_PASSWORD=1');
        (new AdminBootstrapService())->bootstrapFromEnvironment();
        (new AdminBootstrapService())->bootstrapFromEnvironment();

        $model = new UserModel();
        $this->assertNull($model->verifyUser('admin', 'first-password'));
        $this->assertNotNull($model->verifyUser('admin', 'second-password'));
    }

    public function testMissingSecretFailsFreshBootstrap(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required secret is missing');

        (new AdminBootstrapService())->bootstrapFromEnvironment();
    }
}
