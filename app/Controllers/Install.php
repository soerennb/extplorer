<?php

namespace App\Controllers;

use App\Models\UserModel;

class Install extends BaseController
{
    private function getChecks(): array
    {
        return [
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
    }

    public function index()
    {
        $checks = $this->getChecks();

        // Check if writable is writable, if not, show error page only
        if (!$checks['writable']['status']) {
            return view('install/error', ['checks' => $checks]);
        }

        return view('install/index', ['checks' => $checks]);
    }

    public function createAdmin()
    {
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to('install');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        
        if (strlen($password) < 8) {
            return view('install/index', [
                'checks' => $this->getChecks(),
                'error' => 'Password must be at least 8 characters.'
            ]);
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
        $created = $userModel->addUser($username, $password, 'admin', '/', ['Administrators']);
        $user = $userModel->getUser($username);

        if ($created && $user) {
            return redirect()->to('login')->with('message', 'Installation successful! Please login.');
        }

        $error = $created
            ? 'Failed to write user data. Please verify write permissions for the writable directory.'
            : 'Failed to create user. It might already exist.';

        return view('install/index', [
            'checks' => $this->getChecks(),
            'error' => $error
        ]);
    }
}
