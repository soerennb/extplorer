<?php

namespace Tests\Unit;

use App\Services\WebDavCredentialService;
use CodeIgniter\Test\CIUnitTestCase;

final class WebDavCredentialServiceTest extends CIUnitTestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = sys_get_temp_dir() . '/extplorer-dav-credentials-' . bin2hex(random_bytes(8)) . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        @unlink($this->path . '.lock');
        parent::tearDown();
    }

    public function testSecretIsShownOnceStoredHashedAndCanBeRevoked(): void
    {
        $service = new WebDavCredentialService($this->path);
        $created = $service->create('alice', 'Laptop');

        $this->assertStringStartsWith('ex3dav_', $created['secret']);
        $this->assertArrayNotHasKey('password_hash', $created['credential']);
        $this->assertArrayNotHasKey('secret', $service->listForUser('alice')[0]);
        $this->assertStringNotContainsString($created['secret'], (string)file_get_contents($this->path));
        $this->assertTrue($service->verify('alice', $created['secret']));
        $this->assertFalse($service->verify('bob', $created['secret']));
        $this->assertTrue($service->delete('alice', $created['credential']['id']));
        $this->assertFalse($service->verify('alice', $created['secret']));
    }
}
