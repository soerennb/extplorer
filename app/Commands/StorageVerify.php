<?php

namespace App\Commands;

use App\Services\StorageArchiveService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;
use Throwable;

final class StorageVerify extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'storage:verify';
    protected $description = 'Verifies a storage backup manifest and all file checksums.';
    protected $arguments = ['source' => 'Backup .zip path'];

    public function run(array $params)
    {
        try {
            $source = $params[0] ?? null;
            if (!is_string($source) || trim($source) === '') {
                throw new RuntimeException('Backup source is required.');
            }
            $result = (new StorageArchiveService())->verify($source);
            CLI::write("Backup verified: {$result['path']} ({$result['files']} files)", 'green');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error('Storage verification failed: ' . $exception->getMessage());
            return EXIT_ERROR;
        }
    }
}
