<?php

namespace Tests\Unit;

use App\Services\ShareService;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;

class ShareServiceTest extends CIUnitTestCase
{
    private string $testFile;
    private string $usersFile;
    private string|false $usersBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFile = config('Storage')->state . '/test_shares.php';
        if (file_exists($this->testFile)) unlink($this->testFile);
        $this->usersFile = config('Storage')->state . '/users.php';
        $this->usersBackup = is_file($this->usersFile) ? file_get_contents($this->usersFile) : false;
        (new UserModel())->saveUsers([[
            'username' => 'share-owner',
            'password_hash' => password_hash('unused-password', PASSWORD_DEFAULT),
            'role' => 'admin',
            'home_dir' => '/',
            'groups' => [],
            'auth_version' => 1,
            'disabled' => false,
            'locked_until' => 0,
        ]]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->testFile)) unlink($this->testFile);
        if ($this->usersBackup === false) @unlink($this->usersFile);
        else file_put_contents($this->usersFile, $this->usersBackup);
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

    public function testAmbiguousLegacyOrExternalSharePathFailsClosed(): void
    {
        $service = new ShareService($this->testFile);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must be recreated');
        $service->resolveSharePaths(['path' => 'external/secret.txt', 'created_by' => 'admin']);
    }

    public function testInactiveShareOwnerCannotResolvePublishedPath(): void
    {
        $service = new ShareService($this->testFile);
        (new UserModel())->updateUser('share-owner', ['locked_until' => time() + 3600]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not active');
        $service->resolveSharePaths(['path' => 'Home/file.txt', 'created_by' => 'share-owner']);
    }
}
