<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\ShareService;
use App\Services\EmailService;
use App\Services\SettingsService;

class CheckShareExpiration extends BaseCommand
{
    protected $group       = 'eXtplorer';
    protected $name        = 'shares:cleanup';
    protected $description = 'Checks for expired shares and sends notifications.';

    public function run(array $params)
    {
        CLI::write('Starting Share Cleanup...', 'yellow');

        $shareService = new ShareService();
        $stats = $shareService->processCleanup();

        CLI::write("Done. Expired: {$stats['expired']}. Warned: {$stats['warned']}.", 'green');
    }

    private function getAllShares($service)
    {
        // Deprecated, used internally by service now
        return [];
    }

    private function rrmdir($dir) {
        // Deprecated, used internally by service now
    }
}
