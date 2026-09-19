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
        $this->testFile = config('Storage')->state . '/test_shares.php';
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
        $this->assertSame(32, strlen($share['hash']));
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

    public function testExpirationAtCurrentTimestampIsExpired(): void
    {
        $service = new ShareService($this->testFile);
        $share = $service->createShare('AtBoundary', 'admin', null, time());

        $this->assertNull($service->getShare($share['hash']));
        $this->assertNotNull($service->getShareRaw($share['hash']));
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

    public function testShareViewsDoNotExposeSecretsOrTransferMessageMetadata(): void
    {
        $service = new ShareService($this->testFile);
        $share = $service->createShare('Transfers/internal', 'admin', 'secret', null, 'read', [
            'source' => 'transfer',
            'is_transfer' => true,
            'subject' => 'Confidential subject',
            'message' => 'Private message',
            'recipients' => ['recipient@example.test'],
            'sender_email' => 'sender@example.test',
            'file_count' => 1,
            'total_size' => 42,
        ]);

        $ownerView = $service->ownerView($share);
        $this->assertArrayNotHasKey('password_hash', $ownerView);
        $this->assertArrayNotHasKey('message', $ownerView);
        $this->assertArrayNotHasKey('recipients', $ownerView);
        $this->assertArrayNotHasKey('sender_email', $ownerView);
        $this->assertTrue($ownerView['password_protected']);

        $publicView = $service->publicView($share);
        $this->assertArrayNotHasKey('path', $publicView);
        $this->assertArrayNotHasKey('password_hash', $publicView);
        $this->assertSame('internal', $publicView['display_name']);

        $transferView = $service->transferView($share);
        $this->assertSame(1, $transferView['recipient_count']);
        $this->assertArrayNotHasKey('message', $transferView);
        $this->assertArrayNotHasKey('recipients', $transferView);
        $this->assertArrayNotHasKey('sender_email', $transferView);
        $this->assertArrayNotHasKey('path', $transferView);
    }
}
