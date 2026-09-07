<?php

namespace Tests\Unit;

use App\Services\UploadQuarantineService;
use CodeIgniter\Test\CIUnitTestCase;

final class UploadQuarantineServiceTest extends CIUnitTestCase
{
    private string $root;
    private string $targetRoot;
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/extplorer-quarantine-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0700, true);
        $this->targetRoot = config('Storage')->fileManagerRoot . '/.quarantine-test-' . bin2hex(random_bytes(8));
        mkdir($this->targetRoot, 0700, true);

        foreach (['EXTPLORER_UPLOAD_SCAN_MODE', 'EXTPLORER_UPLOAD_QUARANTINE_MAX_MB', 'EXTPLORER_UPLOAD_QUARANTINE_MAX_FILES'] as $key) {
            $value = getenv($key);
            $this->environment[$key] = $value === false ? null : $value;
        }
        putenv('EXTPLORER_UPLOAD_SCAN_MODE=external');
        putenv('EXTPLORER_UPLOAD_QUARANTINE_MAX_MB=1');
        putenv('EXTPLORER_UPLOAD_QUARANTINE_MAX_FILES=10');
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $key => $value) {
            $value === null ? putenv($key) : putenv($key . '=' . $value);
        }
        $this->removeDirectory($this->root);
        $this->removeDirectory($this->targetRoot);
        parent::tearDown();
    }

    public function testCleanResultPromotesOnlyAfterApproval(): void
    {
        $source = $this->root . '/incoming.bin';
        $target = $this->targetRoot . '/file.bin';
        file_put_contents($source, 'untrusted content');

        $service = new UploadQuarantineService($this->root);
        $staged = $service->stage($source, $target, ['owner' => 'alice', 'source' => 'test']);

        $this->assertFileDoesNotExist($source);
        $this->assertFileDoesNotExist($target);
        $this->assertSame('pending', $service->get($staged['id'])['status']);

        $result = $service->markResult($staged['id'], 'clean', 'scanner ok');
        $this->assertSame('clean', $result['status']);
        $this->assertSame('untrusted content', file_get_contents($target));
        $this->assertFileDoesNotExist($staged['payload_path']);
    }

    public function testInfectedResultNeverActivatesTarget(): void
    {
        $source = $this->root . '/malware.bin';
        $target = $this->targetRoot . '/malware.bin';
        file_put_contents($source, 'malicious bytes');

        $service = new UploadQuarantineService($this->root);
        $staged = $service->stage($source, $target);
        $service->markResult($staged['id'], 'infected', 'test detection');

        $this->assertFileDoesNotExist($target);
        $this->assertFileDoesNotExist($staged['payload_path']);
        $this->assertSame('infected', $service->get($staged['id'])['status']);
    }

    public function testTargetOutsideManagedRootIsRejected(): void
    {
        $source = $this->root . '/incoming.bin';
        file_put_contents($source, 'data');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('managed file roots');
        (new UploadQuarantineService($this->root))->stage($source, sys_get_temp_dir() . '/outside.bin');
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory) || is_link($directory)) return;
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path) && !is_link($path)) $this->removeDirectory($path);
            else @unlink($path);
        }
        @rmdir($directory);
    }
}
