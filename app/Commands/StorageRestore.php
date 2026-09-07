<?php

namespace App\Commands;

use App\Services\StorageArchiveService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;
use Throwable;

final class StorageRestore extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'storage:restore';
    protected $description = 'Restores persistent eXtplorer data from a verified backup.';
    protected $arguments = ['source' => 'Backup .zip path'];
    protected $options = ['--force' => 'Required confirmation for restore'];

    public function run(array $params)
    {
        try {
            $source = $params[0] ?? null;
            if (!is_string($source) || trim($source) === '') {
                throw new RuntimeException('Backup source is required.');
            }
            if (CLI::getOption('force') === null && CLI::getOption('--force') === null) {
                throw new RuntimeException('Restore requires --force.');
            }
            $result = (new StorageArchiveService())->restore($source, true);
            CLI::write("Storage restored from {$result['source']}. Pre-restore backup: {$result['pre_restore_backup']}", 'green');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error('Storage restore failed: ' . $exception->getMessage());
            return EXIT_ERROR;
        }
    }
}
