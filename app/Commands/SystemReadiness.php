<?php

namespace App\Commands;

use App\Services\AtomicFileStore;
use App\Services\State\StorageSchemaService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;
use Throwable;

final class SystemReadiness extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'system:readiness';
    protected $description = 'Checks that initialized code and storage are ready to serve traffic.';

    public function run(array $params)
    {
        try {
            $root = rtrim((string)(getenv('EXTPLORER_CODE_ROOT') ?: '/var/www/html/current'), '/');
            $marker = (string)(getenv('EXTPLORER_READINESS_FILE') ?: '/tmp/extplorer-ready');
            if (!is_file($marker) || !is_file($root . '/spark') || !is_dir($root . '/app') || !is_dir($root . '/public')) {
                throw new RuntimeException('Code release is not ready.');
            }

            (new StorageSchemaService())->ensure();
            AtomicFileStore::verifyBackend();
            CLI::write('ready');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error('Readiness check failed: ' . $exception->getMessage());
            return EXIT_ERROR;
        }
    }
}
