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
        $roles = $userModel->getRoles();
        if (empty($roles)) {
            $defaultRoles = [
                'admin' => ['*'],
                'user'  => ['read', 'write', 'upload', 'delete', 'rename', 'archive', 'extract', 'chmod', 'mount_external']
            ];
            $userModel->saveRoles($defaultRoles);
        }

        // 2. Initialize Groups
        $groups = $userModel->getGroups();
        if (empty($groups)) {
            $userModel->saveGroups(['Administrators' => ['admin']]);
        }

        // 3. Create Admin User
        if ($userModel->addUser($username, $password, 'admin', '/', ['Administrators'])) {
            return redirect()->to('login')->with('message', 'Installation successful! Please login.');
        } else {
            return redirect()->back()->with('error', 'Failed to create user. It might already exist.');
        }
    }
}
