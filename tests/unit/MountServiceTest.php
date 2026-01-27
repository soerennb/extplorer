<?php

namespace Tests\Unit;

use App\Services\MountService;
use App\Services\SettingsService;
use CodeIgniter\Test\CIUnitTestCase;

class MountServiceTest extends CIUnitTestCase
{
    private string $mountsPath;
    private string $settingsPath;
    private ?string $mountsBackup;
    private ?string $settingsBackup;
    private array $sessionBackup = [];
    private array $originalAllowlist = [];
    private string $allowedRoot;

    protected function setUp(): void
    {
        parent::setUp();

        helper('auth');

        $this->mountsPath = WRITEPATH . 'mounts.php';
        $this->settingsPath = WRITEPATH . 'settings.php';
        $this->mountsBackup = file_exists($this->mountsPath) ? file_get_contents($this->mountsPath) : null;
        $this->settingsBackup = file_exists($this->settingsPath) ? file_get_contents($this->settingsPath) : null;

        $session = session();
        $this->sessionBackup = [
            'username' => $session->get('username'),
            'permissions' => $session->get('permissions'),
        ];

        $session->set('username', 'test-user');
        $session->set('permissions', ['mount_external']);

        $this->originalAllowlist = config('App')->mountRootAllowlist ?? [];

        $this->allowedRoot = WRITEPATH . 'tests/mount-root';
        if (!is_dir($this->allowedRoot)) {
            mkdir($this->allowedRoot, 0777, true);
        }

        $settingsService = new SettingsService();
        $settingsService->saveSettings([
            'mount_root_allowlist' => [],
        ]);

        config('App')->mountRootAllowlist = [$this->allowedRoot];

        // Start from a clean mounts file.
        if (file_exists($this->mountsPath)) {
            unlink($this->mountsPath);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $session = session();
        foreach ($this->sessionBackup as $key => $value) {
            if ($value === null) {
                $session->remove($key);
            } else {
                $session->set($key, $value);
            }
        }

        config('App')->mountRootAllowlist = $this->originalAllowlist;

        if ($this->mountsBackup === null) {
            if (file_exists($this->mountsPath)) {
                unlink($this->mountsPath);
            }
        } else {
            file_put_contents($this->mountsPath, $this->mountsBackup);
        }

        if ($this->settingsBackup === null) {
            if (file_exists($this->settingsPath)) {
                unlink($this->settingsPath);
            }
        } else {
            file_put_contents($this->settingsPath, $this->settingsBackup);
        }
    }

    public function testUpdateLocalMountNormalizesPath(): void
    {
        $service = new MountService();

        $initialPath = $this->allowedRoot . '/initial';
        $updatedPath = $this->allowedRoot . '/updated';
        if (!is_dir($initialPath)) {
            mkdir($initialPath, 0777, true);
        }
        if (!is_dir($updatedPath)) {
            mkdir($updatedPath, 0777, true);
        }

        $id = $service->addMount('test-user', 'Initial Mount', 'local', [
            'path' => $initialPath,
        ]);

        $updated = $service->updateMount($id, 'test-user', 'Updated Mount', 'local', [
            'path' => $updatedPath,
        ]);

        $this->assertSame($id, $updated['id']);
        $this->assertSame('Updated Mount', $updated['name']);
        $this->assertSame('local', $updated['type']);

        $mount = $service->getMountForUser($id, 'test-user', false);
        $this->assertSame(realpath($updatedPath), $mount['config']['path']);
    }

    public function testTestMountReturnsNormalizedLocalPath(): void
    {
        $service = new MountService();

        $path = $this->allowedRoot . '/test-path';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $result = $service->testMount('test-user', null, 'Test Mount', 'local', [
            'path' => $path,
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(realpath($path), $result['config']['path']);
    }
}
