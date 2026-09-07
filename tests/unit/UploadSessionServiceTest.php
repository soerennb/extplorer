<?php

namespace Tests\Unit;

use App\Services\UploadSessionService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class UploadSessionServiceTest extends CIUnitTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/extplorer_upload_sessions_' . uniqid('', true);
        mkdir($this->root, 0700, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
        parent::tearDown();
    }

    public function testChunksMustAllExistBeforeAssembly(): void
    {
        $service = new UploadSessionService($this->root, 'test-key');
        $created = $service->create('alice', '/', '', 'file.txt', 6, 64 * 1024, 2);
        $first = $service->chunkPath($created['id'], 0);
        file_put_contents($first, 'abc');
        $service->storeChunk($created['id'], 0, 3);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('incomplete');
        $service->assemble($created['id'], $this->root . '/result.txt');
    }

    public function testAssemblyStreamsChunksAndBindsOwner(): void
    {
        $service = new UploadSessionService($this->root, 'test-key');
        $created = $service->create('alice', '/', '', 'file.txt', 6, 64 * 1024, 2);
        $first = $service->chunkPath($created['id'], 0);
        $second = $service->chunkPath($created['id'], 1);
        file_put_contents($first, 'abc');
        file_put_contents($second, 'def');
        $service->storeChunk($created['id'], 0, 3);
        $service->storeChunk($created['id'], 1, 3);

        $manifest = $service->get($created['id']);
        $service->assertOwner($manifest, 'alice');
        $this->expectException(RuntimeException::class);
        $service->assertOwner($manifest, 'bob');
    }

    public function testAssemblyProducesExactFinalFileAndRemovesSession(): void
    {
        $service = new UploadSessionService($this->root, 'test-key');
        $created = $service->create('alice', '/', '', 'file.txt', 6, 64 * 1024, 2);
        file_put_contents($service->chunkPath($created['id'], 0), 'abc');
        file_put_contents($service->chunkPath($created['id'], 1), 'def');
        $service->storeChunk($created['id'], 0, 3);
        $service->storeChunk($created['id'], 1, 3);

        $destination = $this->root . '/result.txt';
        $this->assertSame(6, $service->assemble($created['id'], $destination));
        $this->assertSame('abcdef', file_get_contents($destination));
        $this->expectException(RuntimeException::class);
        $service->get($created['id']);
    }

    public function testDuplicateChunkAndOversizedChunkAreRejected(): void
    {
        $service = new UploadSessionService($this->root, 'test-key');
        $created = $service->create('alice', '/', '', 'file.txt', 3, 64 * 1024, 1);
        $staging = $service->stagingPath($created['id']);
        file_put_contents($staging, 'abc');
        $service->storeChunkFromPath($created['id'], 0, $staging, 3);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already stored');
        $service->storeChunk($created['id'], 0, 3);
    }

    public function testChunkSizeCannotExceedManifestLimit(): void
    {
        $service = new UploadSessionService($this->root, 'test-key');
        $created = $service->create('alice', '/', '', 'file.txt', 3, 64 * 1024, 1);
        file_put_contents($service->chunkPath($created['id'], 0), 'abc');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('metadata');
        $service->storeChunk($created['id'], 0, 64 * 1024 + 1);
    }

    public function testCleanupRemovesExpiredSessionsButKeepsActiveSessions(): void
    {
        $service = new UploadSessionService($this->root, 'test-key');
        $expired = $service->create('alice', '/', '', 'expired.txt', 0, 64 * 1024, 1);
        $active = $service->create('alice', '/', '', 'active.txt', 0, 64 * 1024, 1);

        $expiredManifest = $service->get($expired['id']);
        $expiredManifest['expires_at'] = time() - 1;
        \App\Services\AtomicFileStore::write(
            $this->root . '/' . $expired['id'] . '/session.php',
            $expiredManifest
        );

        $this->assertSame(1, $service->cleanupExpired());
        $this->assertSame($active['id'], $service->get($active['id'])['id']);
        $this->expectException(RuntimeException::class);
        $service->get($expired['id']);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) return;
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }
}
