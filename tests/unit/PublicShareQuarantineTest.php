<?php

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\ShareService;
use App\Services\UploadQuarantineService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\StorageSandbox;

/**
 * Anonymous uploads to an upload-mode share add files. Approving a scanned
 * upload must neither replace existing content nor outlive share revocation.
 */
final class PublicShareQuarantineTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string $inbox;
    private string $hash;
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['EXTPLORER_UPLOAD_SCAN_MODE' => 'external', 'EXTPLORER_UPLOAD_QUARANTINE_MAX_MB' => '1',
            'EXTPLORER_UPLOAD_QUARANTINE_MAX_FILES' => '10', 'EXTPLORER_UPLOAD_QUARANTINE_TTL_SECONDS' => '86400'] as $key => $value) {
            $current = getenv($key);
            $this->environment[$key] = $current === false ? null : $current;
            putenv("{$key}={$value}");
        }
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([['username' => 'owen', 'role' => 'user', 'home_dir' => '/owen', 'auth_version' => 1]]);
        $this->inbox = config('Storage')->fileManagerRoot . '/owen/inbox';
        mkdir($this->inbox, 0700, true);
        file_put_contents($this->inbox . '/report.pdf', 'owner content');
        $this->hash = (new ShareService())->createShare('Home/inbox', 'owen', null, null, 'upload')['hash'];
    }

    protected function tearDown(): void
    {
        $this->sandbox->close();
        foreach ($this->environment as $key => $value) {
            putenv($value === null ? $key : "{$key}={$value}");
        }
        parent::tearDown();
    }

    public function testApprovedPublicUploadNeverReplacesExistingFile(): void
    {
        // 3.1.4 staged public uploads with conflict=replace; pending items
        // from before an upgrade must not overwrite content either.
        $service = new UploadQuarantineService();
        $staged = $service->stage($this->incoming('anonymous content'), $this->inbox . '/report.pdf', $this->metadata('replace'));

        $result = $service->markResult($staged['id'], 'clean');

        $this->assertSame('clean', $result['status']);
        $this->assertSame('owner content', file_get_contents($this->inbox . '/report.pdf'));
        $this->assertSame('anonymous content', file_get_contents($this->inbox . '/report (1).pdf'));
    }

    public function testRevokedShareDiscardsPendingUpload(): void
    {
        $service = new UploadQuarantineService();
        $staged = $service->stage($this->incoming('late content'), $this->inbox . '/late.pdf', $this->metadata('keep_both'));
        (new ShareService())->deleteShare($this->hash);

        $result = $service->markResult($staged['id'], 'clean');

        $this->assertSame('error', $result['status']);
        $this->assertFileDoesNotExist($this->inbox . '/late.pdf');
        $this->assertFileDoesNotExist($staged['payload_path']);
    }

    private function incoming(string $content): string
    {
        $service = new UploadQuarantineService();
        $path = $service->incomingPath();
        file_put_contents($path, $content);
        return $path;
    }

    private function metadata(string $conflict): array
    {
        return ['owner' => 'Public', 'filename' => 'report.pdf', 'relative_path' => '', 'share_hash' => $this->hash,
            'conflict' => $conflict, 'source' => 'public-share-upload'];
    }
}
