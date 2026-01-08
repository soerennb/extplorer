<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;

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
        if (session('role') !== 'admin') {
            return $this->failForbidden('Access denied');
        }
        return true;
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

        if (!$username || !$password) {
            return $this->fail('Username and Password required');
        }

        if ($this->userModel->addUser($username, $password, $role, $homeDir)) {
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
            return $this->respond(['status' => 'success']);
        } else {
            return $this->failNotFound('User not found');
        }
    }

    public function systemInfo()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        return $this->respond([
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
}
