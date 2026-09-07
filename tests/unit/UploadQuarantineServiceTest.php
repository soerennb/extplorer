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

        foreach (['EXTPLORER_UPLOAD_SCAN_MODE', 'EXTPLORER_UPLOAD_QUARANTINE_MAX_MB', 'EXTPLORER_UPLOAD_QUARANTINE_MAX_FILES', 'EXTPLORER_UPLOAD_QUARANTINE_TTL_SECONDS'] as $key) {
            $value = getenv($key);
            $this->environment[$key] = $value === false ? null : $value;
        }
        putenv('EXTPLORER_UPLOAD_SCAN_MODE=external');
        putenv('EXTPLORER_UPLOAD_QUARANTINE_MAX_MB=1');
        putenv('EXTPLORER_UPLOAD_QUARANTINE_MAX_FILES=10');
        putenv('EXTPLORER_UPLOAD_QUARANTINE_TTL_SECONDS=86400');
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

    public function testExpiredPrivateTemporaryFilesAreRemovedButFreshFilesRemain(): void
    {
        $service = new UploadQuarantineService($this->root);
        $stale = $this->root . '/.incoming-stale';
        $fresh = $this->root . '/.payload-fresh';
        file_put_contents($stale, 'stale');
        file_put_contents($fresh, 'fresh');
        touch($stale, time() - 86_401);

        $service->cleanupExpired();

        $this->assertFileDoesNotExist($stale);
        $this->assertFileExists($fresh);
    }

    public function testConcurrentCleanResultsFinalizeExactlyOnce(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the concurrency regression test.');
        }

        $source = $this->root . '/concurrent.bin';
        $target = $this->targetRoot . '/concurrent.bin';
        $resultFile = $this->root . '/child-result';
        file_put_contents($source, 'concurrent content');
        $service = new UploadQuarantineService($this->root);
        $staged = $service->stage($source, $target);

        $pid = pcntl_fork();
        $this->assertNotSame(-1, $pid);
        if ($pid === 0) {
            try {
                (new UploadQuarantineService($this->root))->markResult($staged['id'], 'clean', 'child');
                file_put_contents($resultFile, 'success');
            } catch (\Throwable $exception) {
                file_put_contents($resultFile, 'failure:' . $exception->getMessage());
            }
            exit(0);
        }

        $parentResult = 'failure';
        try {
            $service->markResult($staged['id'], 'clean', 'parent');
            $parentResult = 'success';
        } catch (\Throwable) {
            // Exactly one finalizer is expected to win.
        }
        pcntl_waitpid($pid, $status);

        $childResult = (string)file_get_contents($resultFile);
        $successes = ($parentResult === 'success' ? 1 : 0) + ($childResult === 'success' ? 1 : 0);
        $this->assertSame(1, $successes);
        $this->assertSame('concurrent content', file_get_contents($target));
        $this->assertSame('clean', $service->get($staged['id'])['status']);
    }

    public function testSymlinkedManagedTargetIsRejected(): void
    {
        if (!function_exists('symlink')) {
            $this->markTestSkipped('Symlinks are required for the path regression test.');
        }

        $source = $this->root . '/symlink.bin';
        $outside = $this->root . '/outside';
        $link = $this->targetRoot . '/link';
        mkdir($outside, 0700, true);
        symlink($outside, $link);
        file_put_contents($source, 'data');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('managed file roots');
        (new UploadQuarantineService($this->root))->stage($source, $link . '/escaped.bin');
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
