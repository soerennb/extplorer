<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;
use App\Services\ShareService;
use App\Services\LogService;
use App\Services\TrashService;

class SecurityMigrate extends BaseCommand
{
    protected $group       = 'eXtplorer';
    protected $name        = 'security:migrate';
    protected $description = 'Migrates sensitive JSON files to secure PHP files.';

    public function run(array $params)
    {
        CLI::write('Starting Security Migration...', 'yellow');

        // 1. Migrate Users, Roles, Groups
        CLI::write('Migrating User Model...', 'white');
        $userModel = new UserModel(); // Triggers migration in __construct
        
        // 2. Migrate Shares
        CLI::write('Migrating Share Service...', 'white');
        new ShareService(); // Triggers migration

        // 3. Migrate Logs
        CLI::write('Migrating Log Service...', 'white');
        LogService::getLogs(); // Triggers migration

        // 4. Migrate Trash (Per User)
        CLI::write('Migrating Trash Indexes...', 'white');
        $users = $userModel->getUsers();
        foreach ($users as $user) {
            $username = $user['username'];
            CLI::write(" - Checking trash for user: {$username}", 'white');
            new TrashService($username); // Triggers migration
        }
        
        // Also check 'admin' explicitly if not in users list (though it should be)
        if (!$userModel->getUser('admin')) {
             CLI::write(" - Checking trash for user: admin", 'white');
             new TrashService('admin');
        }

        CLI::write('Migration Complete.', 'green');
    }
}
