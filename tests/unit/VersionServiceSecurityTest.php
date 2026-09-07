<?php

namespace Tests\Unit;

use App\Services\VFS\LocalAdapter;
use App\Services\VersionService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

class VersionServiceSecurityTest extends CIUnitTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = config('Storage')->fileManagerRoot . '/version-security-tests';
        if (!is_dir($this->root)) {
            mkdir($this->root, 0750, true);
        }
    }

    public function testVersionRestoreRejectsPathTraversalIdentifiers(): void
    {
        $path = $this->root . '/document.txt';
        file_put_contents($path, 'current');
        $service = new VersionService('version-security-user');
        $fs = new LocalAdapter($this->root);

        $this->expectException(Exception::class);
        $service->restoreVersion('document.txt', '../../etc/passwd', $fs);
    }

    public function testCreatedVersionCanBeRestoredWithSafeIdentifier(): void
    {
        $path = $this->root . '/restorable.txt';
        file_put_contents($path, 'before');
        $service = new VersionService('version-restore-user-' . bin2hex(random_bytes(6)));
        $fs = new LocalAdapter($this->root);

        $service->createVersion($path, 'restorable.txt');
        $versions = $service->listVersions('restorable.txt');
        $this->assertCount(1, $versions);
        $this->assertMatchesRegularExpression('/\A\d{10,}_[a-f0-9]{12}\.bak\z/', $versions[0]['id']);

        file_put_contents($path, 'after');
        $service->restoreVersion('restorable.txt', $versions[0]['id'], $fs);
        $this->assertSame('before', file_get_contents($path));
    }
}
