<?php

namespace App\Commands;

use App\Services\StorageArchiveService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

final class StorageBackup extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'storage:backup';
    protected $description = 'Creates a verified backup of persistent eXtplorer data.';
    protected $options = ['--destination' => 'Destination .zip path'];

    public function run(array $params)
    {
        try {
            $destination = CLI::getOption('destination');
            $result = (new StorageArchiveService())->backup(is_string($destination) ? $destination : null);
            CLI::write("Backup created: {$result['path']} ({$result['files']} files)", 'green');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error('Storage backup failed: ' . $exception->getMessage());
            return EXIT_ERROR;
        }
    }
}
