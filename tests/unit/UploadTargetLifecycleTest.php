<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\FileUploadService;
use App\Services\SettingsService;
use App\Services\UploadQuarantineService;
use App\Services\UploadTargetResolver;
use App\Services\VFS\LocalAdapter;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\StorageSandbox;

final class UploadTargetLifecycleTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string|false $scanMode;
    private LocalAdapter $fs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        $this->scanMode = getenv('EXTPLORER_UPLOAD_SCAN_MODE');
        putenv('EXTPLORER_UPLOAD_SCAN_MODE=external');
        (new UserModel())->saveUsers([['username' => 'alice', 'role' => 'admin', 'groups' => [], 'auth_version' => 1, 'home_dir' => '/']]);
        $this->fs = new LocalAdapter(config('Storage')->fileManagerRoot);
    }

    protected function tearDown(): void
    {
        putenv($this->scanMode === false ? 'EXTPLORER_UPLOAD_SCAN_MODE' : 'EXTPLORER_UPLOAD_SCAN_MODE=' . $this->scanMode);
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testAuthorizationRevocationPreventsPromotionAndRetainsPayload(): void
    {
        $resolver = new UploadTargetResolver();
        $target = $resolver->capture('alice', ['mode' => 'local'], 'Home/result.txt');
        $service = new UploadQuarantineService();
        $pending = $service->stage($this->source('pending'), $target);
        (new UserModel())->saveRole('admin', ['read']);
        try {
            $service->markResult($pending['id'], 'clean');
            $this->fail('A revoked grant must not promote an upload.');
        } catch (\RuntimeException $error) { $this->assertStringContainsString('authorized', $error->getMessage()); }
        $this->assertFileExists($pending['payload_path']);
        $this->assertNull($this->fs->getMetadata('result.txt'));
    }

    public function testPayloadMutationInvalidatesCleanResult(): void
    {
        $target = (new UploadTargetResolver())->capture('alice', ['mode' => 'local'], 'Home/result.txt');
        $service = new UploadQuarantineService();
        $pending = $service->stage($this->source('original'), $target);
        file_put_contents($pending['payload_path'], 'modified');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('changed after scanning');
        $service->markResult($pending['id'], 'clean');
    }

    public function testQuotaIsRecheckedAtPromotionAndActivationCanBeRetried(): void
    {
        (new SettingsService())->saveSettings(['quota_per_user_mb' => 1]);
        $target = (new UploadTargetResolver())->capture('alice', ['mode' => 'local'], 'Home/result.txt');
        $service = new UploadQuarantineService();
        $pending = $service->stage($this->source('new file'), $target);
        file_put_contents(config('Storage')->fileManagerRoot . '/full.txt', str_repeat('x', 1048576));
        try {
            $service->markResult($pending['id'], 'clean');
            $this->fail('Quota must be checked when scanned bytes become visible.');
        } catch (\RuntimeException $error) { $this->assertStringContainsString('quota', $error->getMessage()); }
        $this->assertFileExists($pending['payload_path']);
        unlink(config('Storage')->fileManagerRoot . '/full.txt');
        $service->markResult($pending['id'], 'clean');
        $this->assertSame('new file', $this->fs->readFile('result.txt'));
    }

    public function testConflictPoliciesUseCurrentTargetAtPromotion(): void
    {
        $resolver = new UploadTargetResolver();
        $service = new UploadQuarantineService();
        $target = $resolver->capture('alice', ['mode' => 'local'], 'Home/result.txt');
        $pending = $service->stage($this->source('new'), $target, ['conflict' => 'keep_both']);
        file_put_contents(config('Storage')->fileManagerRoot . '/result.txt', 'current');
        $service->markResult($pending['id'], 'clean');
        $this->assertSame('current', $this->fs->readFile('result.txt'));
        $this->assertSame('new', $this->fs->readFile('result (1).txt'));
        $pending = $service->stage($this->source('skipped'), $target, ['conflict' => 'skip']);
        $this->assertSame('skipped', $service->markResult($pending['id'], 'clean')['activation_status']);
        $this->assertSame('current', $this->fs->readFile('result.txt'));
    }

    public function testFailedStreamReplacementPreservesExistingBytes(): void
    {
        $root = config('Storage')->fileManagerRoot;
        file_put_contents($root . '/result.txt', 'previous');
        $input = fopen('php://temp', 'w+b');
        fwrite($input, str_repeat('x', 1048577));
        rewind($input);
        $previous = getenv('EXTPLORER_MAX_DOWNLOAD_MB');
        putenv('EXTPLORER_MAX_DOWNLOAD_MB=1');
        try {
            $this->fs->writeStream('result.txt', $input);
            $this->fail('Expected bounded stream failure.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('limit', $error->getMessage());
        } finally {
            fclose($input);
            putenv($previous === false ? 'EXTPLORER_MAX_DOWNLOAD_MB' : 'EXTPLORER_MAX_DOWNLOAD_MB=' . $previous);
        }
        $this->assertSame('previous', $this->fs->readFile('result.txt'));
        $this->assertSame([], glob($root . '/.extplorer-write-*'));
    }

    private function source(string $payload): string
    {
        $path = config('Storage')->runtime . '/incoming-' . bin2hex(random_bytes(6));
        file_put_contents($path, $payload);
        return $path;
    }
}
