<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\VFS\VfsFactory;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\StorageSandbox;

/** The common writable "Public" mount exists only when the operator enables it. */
final class PublicMountTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string|false $environment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->environment = getenv('EXTPLORER_PUBLIC_MOUNT');
        putenv('EXTPLORER_PUBLIC_MOUNT');
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([['username' => 'gina', 'role' => 'user', 'home_dir' => '/gina', 'auth_version' => 1]]);
        mkdir(config('Storage')->root . '/public', 0700, true);
    }

    protected function tearDown(): void
    {
        $this->sandbox->close();
        putenv($this->environment === false ? 'EXTPLORER_PUBLIC_MOUNT' : 'EXTPLORER_PUBLIC_MOUNT=' . $this->environment);
        parent::tearDown();
    }

    public function testExistingDirectoryAloneDoesNotCreateACommonMount(): void
    {
        $this->assertNotContains('Public', $this->mountNames());
        $this->expectException(\RuntimeException::class);
        VfsFactory::localRoot('Public', ['home_dir' => '/gina']);
    }

    public function testOperatorCanEnableTheCommonMount(): void
    {
        putenv('EXTPLORER_PUBLIC_MOUNT=1');
        $this->assertContains('Public', $this->mountNames());
    }

    private function mountNames(): array
    {
        return array_column(VfsFactory::createFileSystem('gina', ['mode' => 'local'])->listDirectory(''), 'name');
    }
}
