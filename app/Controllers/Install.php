<?php

namespace App\Controllers;

use App\Models\UserModel;

class Install extends BaseController
{
    public function index()
    {
        $checks = [
            'php' => [
                'name' => 'PHP Version >= 8.1',
                'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'current' => PHP_VERSION
            ],
            'writable' => [
                'name' => 'Writable Directory Permissions',
                'status' => is_writable(WRITEPATH),
                'path' => WRITEPATH
            ],
            'extensions' => [
                'intl' => extension_loaded('intl'),
                'mbstring' => extension_loaded('mbstring'),
                'json' => extension_loaded('json'),
                'gd' => extension_loaded('gd'),
            ]
        ];

        // Check if writable is writable, if not, show error page only
        if (!$checks['writable']['status']) {
            return view('install/error', ['checks' => $checks]);
        }

        return view('install/index', ['checks' => $checks]);
    }

    public function createAdmin()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('install');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        
        if (strlen($password) < 8) {
            return redirect()->back()->with('error', 'Password must be at least 8 characters.');
        }

        $userModel = new UserModel();

        // 1. Initialize Roles if missing
        $rolesFile = WRITEPATH . 'roles.json';
        $roles = [
            'admin' => ['*'],
            'user'  => ['read', 'write', 'upload', 'delete', 'rename', 'archive', 'extract', 'chmod']
        ];
        // Only write if really empty or missing permissions
        if (!file_exists($rolesFile) || filesize($rolesFile) < 10) {
            file_put_contents($rolesFile, json_encode($roles, JSON_PRETTY_PRINT));
        }

        // 2. Initialize Groups
        $groupsFile = WRITEPATH . 'groups.json';
        if (!file_exists($groupsFile) || filesize($groupsFile) < 10) {
            file_put_contents($groupsFile, json_encode(['Administrators' => ['admin']], JSON_PRETTY_PRINT));
        }

        // 3. Create Admin User
        if ($userModel->addUser($username, $password, 'admin', '/', ['Administrators'])) {
            return redirect()->to('login')->with('message', 'Installation successful! Please login.');
        } else {
            return redirect()->back()->with('error', 'Failed to create user. It might already exist.');
        }
    }
}
