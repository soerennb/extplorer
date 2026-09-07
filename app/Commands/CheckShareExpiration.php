<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\ShareService;
use App\Services\UploadSessionService;

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
        $uploadSessions = (new UploadSessionService())->cleanupExpired();

        CLI::write(
            "Done. Expired shares: {$stats['expired']}. Warned: {$stats['warned']}. "
            . "Expired upload sessions: {$uploadSessions}.",
            'green'
        );
    }
}
