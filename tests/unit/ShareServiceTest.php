<?php

namespace Tests\Unit;

use App\Services\ShareService;
use CodeIgniter\Test\CIUnitTestCase;

class ShareServiceTest extends CIUnitTestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFile = WRITEPATH . 'test_shares.json';
        if (file_exists($this->testFile)) unlink($this->testFile);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->testFile)) unlink($this->testFile);
    }

    public function testCreateShare()
    {
        $service = new ShareService($this->testFile);
        $share = $service->createShare('Documents/Report.pdf', 'admin');

        $this->assertNotEmpty($share['hash']);
        $this->assertEquals('Documents/Report.pdf', $share['path']);
        $this->assertNull($share['password_hash']);
    }

    public function testExpiration()
    {
        $service = new ShareService($this->testFile);
        
        // Future expiry
        $share1 = $service->createShare('A', 'admin', null, time() + 3600);
        $this->assertNotNull($service->getShare($share1['hash']));

        // Past expiry
        $share2 = $service->createShare('B', 'admin', null, time() - 3600);
        $this->assertNull($service->getShare($share2['hash']));
        $this->assertNotNull($service->getShareRaw($share2['hash']));
    }

    public function testPasswordProtection()
    {
        $service = new ShareService($this->testFile);
        $share = $service->createShare('Secret', 'admin', 'mysecret');

        $this->assertTrue($service->verifyPassword($share['hash'], 'mysecret'));
        $this->assertFalse($service->verifyPassword($share['hash'], 'wrongpass'));
        
        // Test hashing
        $this->assertNotEquals('mysecret', $share['password_hash']);
    }

    public function testDeleteShare()
    {
        $service = new ShareService($this->testFile);
        $share = $service->createShare('DeleteMe', 'admin');
        
        $this->assertTrue($service->deleteShare($share['hash']));
        $this->assertNull($service->getShare($share['hash']));
    }

    public function testVerifyPasswordMissingShareIsFalse()
    {
        $service = new ShareService($this->testFile);
        $this->assertFalse($service->verifyPassword('missing-hash', 'anything'));
    }
}
