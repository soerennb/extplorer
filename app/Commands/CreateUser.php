<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;

class CreateUser extends BaseCommand
{
    protected $group       = 'eXtplorer';
    protected $name        = 'install:create_user';
    protected $description = 'Creates a user via CLI (useful for Docker initialization).';
    protected $usage       = 'install:create_user [username] [password] [options]';
    protected $arguments   = [
        'username' => 'The username',
        'password' => 'The password',
    ];
    protected $options = [
        '-role' => 'Role name (default: user)',
        '-group' => 'Group name (default: none)',
    ];

    public function run(array $params)
    {
        $username = $params[0] ?? null;
        $password = $params[1] ?? null;
        
        if (!$username || !$password) {
            CLI::error("Username and password required.");
            return;
        }

        $role = $params['role'] ?? 'user';
        $group = $params['group'] ?? null;
        $groups = $group ? [$group] : [];

        $userModel = new UserModel();
        
        // Ensure roles/groups exist
        $roles = $userModel->getRoles();
        if (empty($roles)) {
            $userModel->saveRoles(['admin' => ['*'], 'user' => ['read', 'write']]);
        }
        
        $allGroups = $userModel->getGroups();
        if ($group && !isset($allGroups[$group])) {
            $allGroups[$group] = [$role];
            $userModel->saveGroups($allGroups);
        }

        if ($userModel->addUser($username, $password, $role, '/', $groups)) {
            CLI::write("User '{$username}' created successfully.", 'green');
        } else {
            CLI::error("Failed to create user (might already exist).");
        }
    }
}
