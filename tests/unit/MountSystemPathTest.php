<?php

namespace Tests\Unit;

use App\Services\MountService;
use App\Services\SettingsService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\StorageSandbox;

/**
 * The mount root allowlist stays editable in the web settings, but kernel
 * interfaces and the configured secret files can never be mounted.
 */
final class MountSystemPathTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string $secrets;
    private string|false $environment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        $this->secrets = sys_get_temp_dir() . '/ex3-secrets-' . bin2hex(random_bytes(4));
        mkdir($this->secrets . '/keys', 0700, true);
        mkdir($this->secrets . '/public', 0700, true);
        file_put_contents($this->secrets . '/keys/encryption.key', 'key');
        $this->environment = getenv('EXTPLORER_ENCRYPTION_KEY_FILE');
        putenv('EXTPLORER_ENCRYPTION_KEY_FILE=' . $this->secrets . '/keys/encryption.key');
    }

    protected function tearDown(): void
    {
        putenv($this->environment === false ? 'EXTPLORER_ENCRYPTION_KEY_FILE' : 'EXTPLORER_ENCRYPTION_KEY_FILE=' . $this->environment);
        unlink($this->secrets . '/keys/encryption.key');
        rmdir($this->secrets . '/keys');
        rmdir($this->secrets . '/public');
        rmdir($this->secrets);
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testKernelInterfacesAndSecretFilesCannotBeMountedEvenWhenAllowlisted(): void
    {
        (new SettingsService())->saveSettings(['mount_root_allowlist' => ['/proc', $this->secrets]]);
        foreach (['/proc', '/proc/sys', $this->secrets, $this->secrets . '/keys'] as $path) {
            try {
                (new MountService())->assertLocalPathAllowed($path);
                $this->fail("Mounted {$path}");
            } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
                throw $failure;
            } catch (\Exception $exception) {
                $this->assertStringContainsString('protected', $exception->getMessage(), $path);
            }
        }

        // Unrelated directories below an allowlisted root remain mountable.
        $this->assertSame(realpath($this->secrets . '/public'), (new MountService())->assertLocalPathAllowed($this->secrets . '/public'));
    }
}
