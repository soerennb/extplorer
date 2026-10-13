<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\FileUploadPolicy;
use App\Services\SettingsService;
use App\Services\VFS\LocalAdapter;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\StorageSandbox;

final class FileUploadPolicyTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([['username' => 'alice', 'home_dir' => '/', 'role' => 'user']]);
    }

    protected function tearDown(): void
    {
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testQuotaUsesReplacementDeltaAndAllowsExactLimit(): void
    {
        $root = config('Storage')->fileManagerRoot;
        file_put_contents($root . '/existing.bin', str_repeat('A', 1024 * 1024));
        (new SettingsService())->saveSettings(['quota_per_user_mb' => 2]);
        $policy = new FileUploadPolicy();
        $fs = new LocalAdapter($root);
        $this->assertTrue($policy->withAccountQuota('alice', $fs, 'existing.bin', 2 * 1024 * 1024, fn() => true));
        $this->assertTrue($policy->withAccountQuota('alice', $fs, 'new.bin', 1024 * 1024, fn() => true));
        $this->expectException(\RuntimeException::class);
        $policy->withAccountQuota('alice', $fs, 'new.bin', 1024 * 1024 + 1, fn() => true);
    }

    public function testRelativeFoldersAreNormalizedAndTraversalRejected(): void
    {
        $policy = new FileUploadPolicy();
        $this->assertSame(['folder', 'file.txt'], $policy->relativeSegments('folder\\file.txt'));
        $this->assertSame('Home/folder/file.txt', $policy->target('Home', 'ignored.txt', 'folder/file.txt')['path']);
        $this->expectException(\RuntimeException::class);
        $policy->relativeSegments('../file.txt');
    }

    public function testFilenameRejectsPathComponents(): void
    {
        $this->expectException(\RuntimeException::class);
        (new FileUploadPolicy())->filename('../../evil.txt');
    }

    public function testUploadSizeLimitIsApplied(): void
    {
        $this->assertTrue((new FileUploadPolicy())->exceedsSize(2 * 1024 * 1024, ['upload_max_file_mb' => 1]));
    }
}
