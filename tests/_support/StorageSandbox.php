<?php

namespace Tests\Support;

use App\Services\AtomicFileStore;
use App\Services\LogService;

/** Isolates both filesystem payloads and backend metadata for behavior tests. */
final class StorageSandbox
{
    public readonly string $root;
    private array $original;
    private string|false $driver;

    public function __construct(string $driver = 'file')
    {
        $storage = config('Storage');
        $this->original = get_object_vars($storage);
        $this->driver = getenv('EXTPLORER_STATE_DRIVER');
        $this->root = sys_get_temp_dir() . '/extplorer-test-' . bin2hex(random_bytes(8));
        foreach (['root' => '', 'state' => 'config', 'logs' => 'logs', 'session' => 'session',
            'uploads' => 'uploads', 'fileManagerRoot' => 'files', 'shared' => 'shared', 'trash' => 'trash',
            'versions' => 'versions', 'cache' => 'cache', 'runtime' => 'runtime', 'backups' => 'backups'] as $property => $suffix) {
            $storage->$property = $this->root . ($suffix === '' ? '' : '/' . $suffix);
            if (!is_dir($storage->$property)) mkdir($storage->$property, 0700, true);
        }
        putenv('EXTPLORER_STATE_DRIVER=' . $driver);
        AtomicFileStore::reset();
        self::resetLogPath();
    }

    public function close(): void
    {
        AtomicFileStore::reset();
        putenv($this->driver === false ? 'EXTPLORER_STATE_DRIVER' : 'EXTPLORER_STATE_DRIVER=' . $this->driver);
        foreach ($this->original as $property => $value) config('Storage')->$property = $value;
        self::resetLogPath();
        $this->remove($this->root);
    }

    /** LogService caches its document path; it must follow the active storage root. */
    private static function resetLogPath(): void
    {
        (new \ReflectionProperty(LogService::class, 'filePath'))->setValue(null, null);
    }

    private function remove(string $path): void
    {
        if (is_dir($path) && !is_link($path)) {
            foreach (scandir($path) as $entry) if ($entry !== '.' && $entry !== '..') $this->remove($path . '/' . $entry);
            rmdir($path);
        } elseif (file_exists($path) || is_link($path)) {
            unlink($path);
        }
    }
}
