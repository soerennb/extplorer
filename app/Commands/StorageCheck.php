<?php

namespace App\Commands;

use App\Services\AtomicFileStore;
use App\Services\RedisHealthService;
use App\Services\State\StorageSchemaService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;
use RuntimeException;
use Throwable;

final class StorageCheck extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'storage:check';
    protected $description = 'Verifies configured persistent, session and cache backends.';

    public function run(array $params)
    {
        try {
            $storage = config('Storage');
            $this->checkDirectories($storage);
            (new StorageSchemaService())->ensure();
            AtomicFileStore::verifyBackend();
            $this->checkRedis($storage);

            CLI::write(json_encode([
                'status' => 'ok',
                'state_driver' => $storage->stateDriver,
                'session_driver' => $storage->sessionDriver,
                'cache_driver' => $storage->cacheDriver,
            ], JSON_THROW_ON_ERROR), 'green');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error('Storage check failed: ' . $exception->getMessage());
            return EXIT_ERROR;
        }
    }

    private function checkDirectories(object $storage): void
    {
        foreach ([$storage->root, $storage->state, $storage->logs, $storage->session, $storage->uploads, $storage->cache, $storage->runtime, $storage->backups] as $path) {
            if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException("Unable to create storage directory: {$path}");
            }
            if (!is_writable($path)) {
                throw new RuntimeException("Storage directory is not writable: {$path}");
            }
        }
    }

    private function checkRedis(object $storage): void
    {
        if ($storage->sessionDriver !== 'redis' && $storage->cacheDriver !== 'redis') {
            return;
        }
        (new RedisHealthService())->check();

        if ($storage->cacheDriver === 'redis') {
            $cache = Services::cache();
            if (str_contains(get_class($cache), 'DummyHandler')) {
                throw new RuntimeException('Redis cache is configured but CodeIgniter selected the dummy handler.');
            }
            $key = 'storage-check-' . bin2hex(random_bytes(8));
            if (!$cache->save($key, 'ok', 30) || $cache->get($key) !== 'ok') {
                throw new RuntimeException('Redis cache health check failed.');
            }
            $cache->delete($key);
        }
    }
}
