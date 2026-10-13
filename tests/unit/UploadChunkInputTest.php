<?php

namespace Tests\Unit;

use App\Services\UploadChunkInput;
use App\Services\UploadSessionService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class UploadChunkInputTest extends CIUnitTestCase
{
    private ?StorageSandbox $sandbox = null;

    protected function tearDown(): void
    {
        $this->sandbox?->close();
        parent::tearDown();
    }

    public static function transports(): iterable
    {
        yield 'binary PUT' => [false];
        yield 'legacy multipart PUT' => [true];
    }

    #[DataProvider('transports')]
    public function testChunksPreserveBinaryBytesAndFailedActivationIsRetryable(bool $multipart): void
    {
        $this->sandbox = new StorageSandbox();
        $payload = "first\0\xff--fixture-boundary\r\nlast";
        $sessions = new UploadSessionService(null, 'test-key');
        $created = $sessions->create('alice', 'Home', '', 'payload.txt', strlen($payload), 65536, 1);
        $request = Services::request();
        $request->setMethod('PUT');
        $request->setHeader('Content-Type', $multipart ? 'multipart/form-data; boundary=fixture-boundary' : 'application/octet-stream');
        $request->setBody($multipart ? '--fixture-boundary' . "\r\nContent-Disposition: form-data; name=\"file\"; filename=\"blob\"\r\nContent-Type: application/octet-stream\r\n\r\n" . $payload . "\r\n--fixture-boundary--\r\n" : $payload);
        (new UploadChunkInput())->store($request, $sessions, $created['id'], 0);
        try {
            $sessions->finalize($created['id'], static function (): array { throw new \RuntimeException('offline'); });
            $this->fail('Expected activation failure.');
        } catch (\RuntimeException $error) { $this->assertSame('offline', $error->getMessage()); }
        $this->assertSame([], $sessions->missingChunks($sessions->get($created['id'])));
        $result = $sessions->finalize($created['id'], static function (array $manifest, string $path): array { return ['payload' => file_get_contents($path)]; });
        $this->assertSame($payload, $result['payload']);
        $this->assertDirectoryDoesNotExist(config('Storage')->uploads . '/chunks/' . $created['id']);
    }
}
