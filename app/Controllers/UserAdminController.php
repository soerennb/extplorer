<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use App\Services\LogService;

class UserAdminController extends BaseController
{
    use ResponseTrait;

    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    private function checkAdmin()
    {
        if (!can('admin_users')) {
            return $this->failForbidden('Access denied');
        }
        return true;
    }

    // --- Roles ---

    public function getRoles()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        return $this->respond($this->userModel->getRoles());
    }

    public function saveRole()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        $json = $this->request->getJSON();
        $name = $json->name ?? '';
        $permissions = $json->permissions ?? [];

        if (!$name) return $this->fail('Role name required');

        $roles = $this->userModel->getRoles();
        $roles[$name] = $permissions;
        $this->userModel->saveRoles($roles);
        LogService::log('Save Role', $name);
        return $this->respond(['status' => 'success']);
    }

    public function deleteRole($name = null)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        if (!$name) return $this->fail('Role name required');

        $roles = $this->userModel->getRoles();
        if (isset($roles[$name])) {
            unset($roles[$name]);
            $this->userModel->saveRoles($roles);
            LogService::log('Delete Role', $name);
            return $this->respond(['status' => 'success']);
        }
        return $this->failNotFound('Role not found');
    }

    // --- Groups ---

    public function getGroups()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        return $this->respond($this->userModel->getGroups());
    }

    public function saveGroup()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        $json = $this->request->getJSON();
        $name = $json->name ?? '';
        $roles = $json->roles ?? [];

        if (!$name) return $this->fail('Group name required');

        $groups = $this->userModel->getGroups();
        $groups[$name] = $roles;
        $this->userModel->saveGroups($groups);
        LogService::log('Save Group', $name);
        return $this->respond(['status' => 'success']);
    }

    public function deleteGroup($name = null)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        if (!$name) return $this->fail('Group name required');

        $groups = $this->userModel->getGroups();
        if (isset($groups[$name])) {
            unset($groups[$name]);
            $this->userModel->saveGroups($groups);
            LogService::log('Delete Group', $name);
            return $this->respond(['status' => 'success']);
        }
        return $this->failNotFound('Group not found');
    }

    public function index()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $users = $this->userModel->getUsers();
        // Remove sensitive data
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }
        return $this->respond($users);
    }

    public function create()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $json = $this->request->getJSON();
        $username = $json->username ?? '';
        $password = $json->password ?? '';
        $role = $json->role ?? 'user';
        $homeDir = $json->home_dir ?? '/';
        $allowedExt = $json->allowed_extensions ?? '';
        $blockedExt = $json->blocked_extensions ?? '';

        if (!$username || !$password) {
            return $this->fail('Username and Password required');
        }

        if (strlen($password) < 8) {
            return $this->fail('Password must be at least 8 characters long');
        }

        if ($this->userModel->addUser($username, $password, $role, $homeDir, [], $allowedExt, $blockedExt)) {
            LogService::log('Create User', $username);
            return $this->respondCreated(['status' => 'success']);
        } else {
            return $this->fail('User already exists');
        }
    }

    public function update($username = null)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        if (!$username) return $this->fail('Username required');

        $data = json_decode(json_encode($this->request->getJSON()), true);

        if ($this->userModel->updateUser($username, $data)) {
            LogService::log('Update User', $username);
            return $this->respond(['status' => 'success']);
        } else {
            return $this->failNotFound('User not found');
        }
    }

    public function delete($username = null)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        if (!$username) return $this->fail('Username required');

        // Prevent deleting self
        if ($username === session('username')) {
            return $this->fail('Cannot delete yourself');
        }

        if ($this->userModel->deleteUser($username)) {
            LogService::log('Delete User', $username);
            return $this->respond(['status' => 'success']);
        } else {
            return $this->failNotFound('User not found');
        }
    }

    public function systemInfo()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        return $this->respond([
            'app_version' => config('App')->version,
            'php_version' => PHP_VERSION,
            'server_os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => implode(', ', get_loaded_extensions()),
            'disk_free' => disk_free_space(WRITEPATH),
            'disk_total' => disk_total_space(WRITEPATH),
        ]);
    }

    public function getLogs()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;
        return $this->respond(LogService::getLogs());
    }
}
