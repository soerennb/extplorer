<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\AtomicFileStore;
use App\Services\MountService;
use App\Services\VFS\VfsFactory;
use CodeIgniter\Test\CIUnitTestCase;

class VfsFactoryMountTest extends CIUnitTestCase
{
    private string $usersPath;
    private string $mountsPath;
    private ?string $usersBackup = null;
    private ?string $mountsBackup = null;
    private string $allowedRoot;
    private array $originalAllowlist = [];

    protected function setUp(): void
    {
        parent::setUp();

        $state = config('Storage')->state;
        $this->usersPath = $state . '/users.php';
        $this->mountsPath = $state . '/mounts.php';
        $this->usersBackup = file_exists($this->usersPath) ? file_get_contents($this->usersPath) : null;
        $this->mountsBackup = file_exists($this->mountsPath) ? file_get_contents($this->mountsPath) : null;
        $this->originalAllowlist = config('App')->mountRootAllowlist ?? [];
        $this->allowedRoot = sys_get_temp_dir() . '/extplorer3-vfs-mount-' . bin2hex(random_bytes(8));
        mkdir($this->allowedRoot, 0777, true);
        config('App')->mountRootAllowlist = [$this->allowedRoot];

        (new UserModel())->saveUsers([
            [
                'username' => 'vfs-mount-user',
                'password_hash' => password_hash('A-valid-test-password-123!', PASSWORD_DEFAULT),
                'role' => 'user',
                'home_dir' => '/',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->usersBackup === null) {
            @unlink($this->usersPath);
        } else {
            file_put_contents($this->usersPath, $this->usersBackup);
        }

        if ($this->mountsBackup === null) {
            @unlink($this->mountsPath);
        } else {
            file_put_contents($this->mountsPath, $this->mountsBackup);
        }

        config('App')->mountRootAllowlist = $this->originalAllowlist;
        $this->removeDirectory($this->allowedRoot);

        parent::tearDown();
    }

    public function testMalformedPersistedMountIsSkippedAndHealthIsRecorded(): void
    {
        $mountId = 'mnt_legacy_invalid_alias';
        AtomicFileStore::write($this->mountsPath, [
            $mountId => [
                'id' => $mountId,
                'user' => 'vfs-mount-user',
                'name' => '../escape',
                'type' => 'local',
                'config' => ['path' => $this->allowedRoot],
            ],
        ]);

        $filesystem = VfsFactory::createFileSystem('vfs-mount-user');
        $mountNames = array_column($filesystem->listDirectory('/'), 'name');

        $this->assertNotContains('../escape', $mountNames);
        $health = (new MountService())->getMountHealth($mountId, 'vfs-mount-user');
        $this->assertSame('unhealthy', $health['status']);
        $this->assertStringContainsString('Invalid mount alias', $health['error']);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory) || is_link($directory)) {
            return;
        }
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            if (is_link($path) || is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }
        rmdir($directory);
    }
}
