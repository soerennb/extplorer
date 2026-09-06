<?php

namespace Tests\Unit;

use App\Services\AtomicFileStore;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class AtomicFileStoreTest extends CIUnitTestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = config('Storage')->runtime . '/tests/atomic-state.php';
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
        parent::tearDown();
    }

    public function testWritesProtectedStateAtomically(): void
    {
        AtomicFileStore::write($this->path, ['version' => 1, 'items' => ['a']]);

        $this->assertSame(['version' => 1, 'items' => ['a']], AtomicFileStore::read($this->path));
        $this->assertStringStartsWith('<?php die("Access denied"); ?>', (string)file_get_contents($this->path));
        $this->assertSame(0640, fileperms($this->path) & 0777);
    }

    public function testMalformedStateFailsClosed(): void
    {
        file_put_contents($this->path, '<?php die("Access denied"); ?>' . PHP_EOL . '{invalid');

        $this->expectException(RuntimeException::class);
        AtomicFileStore::read($this->path);
    }
}
