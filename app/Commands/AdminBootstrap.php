<?php

namespace App\Commands;

use App\Services\AdminBootstrapService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

class AdminBootstrap extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'admin:bootstrap';
    protected $description = 'Initializes the administrator account from Docker secrets.';

    public function run(array $params)
    {
        try {
            CLI::write((new AdminBootstrapService())->bootstrapFromEnvironment(), 'green');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error($exception->getMessage());
            return EXIT_ERROR;
        }
    }
}
