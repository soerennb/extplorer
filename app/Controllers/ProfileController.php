<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;

class ProfileController extends BaseController
{
    use ResponseTrait;

    public function updatePassword()
    {
        $json = $this->request->getJSON();
        $password = $json->password ?? '';

        if (!$password) return $this->fail('Password required');

        if (strlen($password) < 8) {
            return $this->fail('Password must be at least 8 characters long');
        }

        $username = session('username');
        if (!$username) return $this->failForbidden('Not logged in');

        $userModel = new UserModel();
        if ($userModel->changePassword($username, $password)) {
            return $this->respond(['status' => 'success']);
        } else {
            return $this->fail('Failed to update password');
        }
    }
}
