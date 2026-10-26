<?php

namespace App\Commands;

use App\Services\ExternalAuth\ExternalState;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class ExternalAuthCleanup extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'auth:cleanup';
    protected $description = 'Remove expired external session, event replay and logout transaction records.';

    public function run(array $params): int
    {
        CLI::write(json_encode(ExternalState::cleanup(), JSON_THROW_ON_ERROR));
        return EXIT_SUCCESS;
    }
}
