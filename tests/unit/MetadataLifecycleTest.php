<?php

namespace Tests\Unit;

use App\Services\AtomicFileStore;
use App\Services\StagingResourceService;
use App\Services\TrashService;
use App\Services\UploadSessionService;
use App\Services\VFS\LocalAdapter;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class MetadataLifecycleTest extends CIUnitTestCase
{
    private ?StorageSandbox $sandbox = null;

    protected function tearDown(): void
    {
        $this->sandbox?->close();
        parent::tearDown();
    }

    public static function backends(): iterable
    {
        yield 'file' => ['file'];
        yield 'sqlite' => ['sqlite'];
    }

    #[DataProvider('backends')]
    public function testTrashSurvivesNewServiceAndCanBeRestored(string $driver): void
    {
        $this->sandbox = new StorageSandbox($driver);
        $root = config('Storage')->fileManagerRoot;
        file_put_contents($root . '/example.txt', 'original');
        (new TrashService('alice'))->moveToTrash($root . '/example.txt', 'example.txt');
        $service = new TrashService('alice');
        $items = $service->listItems();
        $this->assertCount(1, $items);
        $service->restore($items[0]['id'], new LocalAdapter($root));
        $this->assertSame('original', file_get_contents($root . '/example.txt'));
        $this->assertSame([], (new TrashService('alice'))->listItems());
    }

    #[DataProvider('backends')]
    public function testActiveSessionSurvivesCleanupAndAbortRemovesMetadata(string $driver): void
    {
        $this->sandbox = new StorageSandbox($driver);
        $root = config('Storage')->uploads . '/chunks';
        $service = new UploadSessionService($root, 'test-key');
        $active = $service->create('alice', 'Home', '', 'file.txt', 3, 65536, 1);
        $expired = $service->create('alice', 'Home', '', 'old.txt', 0, 65536, 1);
        $path = $root . '/' . $expired['id'] . '/session.php';
        $manifest = AtomicFileStore::read($path);
        $manifest['expires_at'] = time() - 1;
        AtomicFileStore::write($path, $manifest);
        $this->assertSame(1, $service->cleanupExpired());
        $this->assertFalse(AtomicFileStore::exists($path));
        $this->assertDirectoryExists($root . '/' . $active['id']);
        $service->abort($active['id']);
        $this->assertFalse(AtomicFileStore::exists($root . '/' . $active['id'] . '/session.php'));
        $this->assertDirectoryDoesNotExist($root . '/' . $active['id']);
    }

    #[DataProvider('backends')]
    public function testLegacySessionResumesAndAssemblyConsumesMetadata(string $driver): void
    {
        $this->sandbox = new StorageSandbox($driver);
        $root = config('Storage')->uploads . '/chunks';
        $service = new UploadSessionService($root, 'test-key');
        $arguments = ['alice', 'file-key', 'Home', '', 'file.txt', 1, 3, 65536, 'replace'];
        $created = $service->legacy(...$arguments);
        $this->assertSame($created['id'], $service->legacy(...$arguments)['id']);
        file_put_contents($service->chunkPath($created['id'], 0), 'abc');
        $service->storeChunk($created['id'], 0, 3);
        $destination = config('Storage')->fileManagerRoot . '/result.txt';
        $this->assertSame(3, $service->assemble($created['id'], $destination));
        $this->assertSame('abc', file_get_contents($destination));
        $this->assertFalse(AtomicFileStore::exists($root . '/' . $created['id'] . '/session.php'));
    }
}
