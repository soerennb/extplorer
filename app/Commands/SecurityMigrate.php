<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\DataMigrationService;
use Throwable;

class SecurityMigrate extends BaseCommand
{
    protected $group       = 'eXtplorer';
    protected $name        = 'security:migrate';
    protected $description = 'Migrates sensitive JSON files to secure PHP files.';

    public function run(array $params)
    {
        try {
            CLI::write('Starting versioned data migration...', 'yellow');
            $version = (new DataMigrationService())->run();
            CLI::write("Data migration complete (schema version {$version}).", 'green');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error('Data migration failed: ' . $exception->getMessage());
            return EXIT_ERROR;
        }
    }
}
