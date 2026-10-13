<?php

namespace Tests\Unit;

use App\Services\EmailService;
use App\Services\SettingsService;
use App\Services\ShareService;
use App\Services\TransferPolicy;
use App\Services\TransferService;
use App\Services\TransferStagingService;
use App\Services\VFS\LocalAdapter;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\StorageSandbox;

final class TransferLifecycleTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private TransferStagingService $staging;
    private string $id = '0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        $this->staging = new TransferStagingService();
        file_put_contents(config('Storage')->fileManagerRoot . '/source.txt', 'abc');
        $this->staging->stage('alice', $this->id, new LocalAdapter(config('Storage')->fileManagerRoot), ['source.txt'], 1024);
    }

    protected function tearDown(): void
    {
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testNotificationFailureRetainsSessionAndRollsBackPublishedFiles(): void
    {
        $shares = new ShareService();
        $service = new TransferService($shares, $this->email(false), $this->staging);
        try {
            $service->send('alice', $this->request());
            $this->fail('Expected delivery failure.');
        } catch (\RuntimeException $exception) { $this->assertSame(502, $exception->getCode()); }
        $this->assertSame([], $shares->getAllShares());
        $this->assertSame('abc', file_get_contents($this->staging->directory('alice', $this->id) . '/source.txt'));
        $this->assertSame([], glob(config('Storage')->uploads . '/shares/*'));
    }

    public function testQuotaFailureRetainsSourceAndRetryCanSucceed(): void
    {
        (new SettingsService())->saveSettings(['quota_per_user_mb' => 1]);
        $shares = new ShareService();
        $existing = $shares->createShare('1111111111111111', 'alice', null, time() + 3600, 'read',
            ['source' => 'transfer', 'is_transfer' => true, 'total_size' => 1024 * 1024]);
        $service = new TransferService($shares, $this->email(true), $this->staging);
        try {
            $service->send('alice', $this->request());
            $this->fail('Expected quota failure.');
        } catch (\RuntimeException $exception) { $this->assertStringContainsString('quota', $exception->getMessage()); }
        $this->assertFileExists($this->staging->directory('alice', $this->id) . '/source.txt');
        $shares->deleteShare($existing['hash']);
        $result = $service->send('alice', $this->request());
        $this->assertSame('success', $result['status']);
        $published = array_values($shares->getAllShares())[0];
        $this->assertSame('abc', file_get_contents($shares->resolveTransferDirectory($published['path'], true) . '/source.txt'));
        $this->assertDirectoryDoesNotExist($this->staging->directory('alice', $this->id));
    }

    public function testChangedSourceDoesNotOverwriteAnAlreadyStagedFile(): void
    {
        $fs = new class(config('Storage')->fileManagerRoot) extends LocalAdapter {
            public function getMetadata(string $path): ?array { return ['type' => 'file', 'size' => 6]; }
        };
        try {
            $this->staging->stage('alice', $this->id, $fs, ['source.txt'], 1024);
            $this->fail('Expected changed source failure.');
        } catch (\RuntimeException $exception) { $this->assertStringContainsString('changed', $exception->getMessage()); }
        $this->assertSame('abc', file_get_contents($this->staging->directory('alice', $this->id) . '/source.txt'));
    }

    public function testChunkUploadUsesOffsetAndCanResume(): void
    {
        $source = config('Storage')->runtime . '/chunk';
        file_put_contents($source, 'abc');
        $first = $this->staging->upload('alice', $this->id, 'other.txt', $source, 0, 6, 0, 2, 1024);
        $this->assertSame(['status' => 'partial', 'uploaded' => 3], $first);
        $this->assertSame($first, $this->staging->status('alice', $this->id, 'other.txt'));
        $last = $this->staging->upload('alice', $this->id, 'other.txt', $source, 3, 6, 1, 2, 1024);
        $this->assertSame(['status' => 'complete', 'uploaded' => 6], $last);
        $this->assertSame('abcabc', file_get_contents($this->staging->directory('alice', $this->id) . '/other.txt'));
    }

    public function testPartialDeliveryKeepsLinksAlreadySentAndReportsFailedRecipients(): void
    {
        $email = new class extends EmailService {
            public function __construct() {}
            public function isDeliveryReady(?array $settings = null): bool { return true; }
            public function sendTransferNotification(array $transfer, string $link): bool { return $transfer['recipient_email'] === 'ok@example.test'; }
            public function invalidateDeliveryVerification(): void {}
        };
        $request = $this->request();
        $request['recipients'] = ['ok@example.test', 'failed@example.test'];
        $shares = new ShareService();
        $result = (new TransferService($shares, $email, $this->staging))->send('alice', $request);
        $this->assertSame('success', $result['status']);
        $this->assertSame(['failed@example.test'], $result['email_failures']);
        $share = array_values($shares->getAllShares())[0];
        $this->assertFileExists($shares->resolveTransferDirectory($share['path'], true) . '/source.txt');
    }

    private function request(): array
    {
        return (new TransferPolicy())->request(['sessionId' => $this->id, 'recipients' => ['recipient@example.test']], null);
    }

    private function email(bool $succeeds): EmailService
    {
        return new class($succeeds) extends EmailService {
            public function __construct(private bool $succeeds) {}
            public function isDeliveryReady(?array $settings = null): bool { return true; }
            public function sendTransferNotification(array $transfer, string $link): bool { return $this->succeeds; }
            public function invalidateDeliveryVerification(): void {}
        };
    }
}
