<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;

class ProfileController extends BaseController
{
    use ResponseTrait;

    public function getDetails()
    {
        $username = session('username');
        if (!$username) return $this->failForbidden('Not logged in');

        $userModel = new UserModel();
        $user = $userModel->getUser($username);
        
        if (!$user) return $this->failNotFound('User not found');

        // Filter sensitive data
        return $this->respond([
            'username' => $user['username'],
            'role' => $user['role'],
            'home_dir' => $user['home_dir'],
            '2fa_enabled' => $user['2fa_enabled'] ?? false,
            'allowed_extensions' => $user['allowed_extensions'] ?? '',
            'blocked_extensions' => $user['blocked_extensions'] ?? '',
            'system_blocklist' => ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'pl', 'py', 'rb', 'cgi', 'exe', 'sh', 'bat', 'cmd', 'htaccess', 'htpasswd']
        ]);
    }

    public function setup2fa()
    {
        $username = session('username');
        if (!$username) return $this->failForbidden('Not logged in');

        $service = new \App\Services\TwoFactorService();
        $secret = $service->generateSecret();
        $qr = $service->getQrCodeUrl($username, $secret);

        return $this->respond([
            'secret' => $secret,
            'qr' => $qr
        ]);
    }

    public function enable2fa()
    {
        $username = session('username');
        if (!$username) return $this->failForbidden('Not logged in');

        $json = $this->request->getJSON();
        $secret = $json->secret ?? '';
        $code = $json->code ?? '';

        if (!$secret || !$code) return $this->fail('Secret and Code required');

        $service = new \App\Services\TwoFactorService();
        if ($service->verifyCode($secret, $code)) {
            $userModel = new UserModel();
            $recoveryCodes = $service->generateRecoveryCodes();
            
            $userModel->updateUser($username, [
                '2fa_secret' => $secret,
                '2fa_enabled' => true,
                'recovery_codes' => $recoveryCodes
            ]);
            
            return $this->respond([
                'status' => 'success',
                'recovery_codes' => $recoveryCodes
            ]);
        }

        return $this->fail('Invalid verification code');
    }

    public function disable2fa()
    {
        $username = session('username');
        if (!$username) return $this->failForbidden('Not logged in');

        $json = $this->request->getJSON();
        $password = (string)($json->password ?? '');
        $code = trim((string)($json->code ?? ''));

        if ($password === '' && $code === '') {
            return $this->fail('Password or authenticator code is required');
        }

        $userModel = new UserModel();
        $reauthenticated = false;

        if ($password !== '') {
            $reauthenticated = (bool)$userModel->verifyUser($username, $password);
        }

        if (!$reauthenticated && $code !== '') {
            $secret = $userModel->get2faSecret($username);
            if (!$secret) {
                return $this->fail('Two-factor authentication is not enabled');
            }
            $service = new \App\Services\TwoFactorService();
            $reauthenticated = $service->verifyCode($secret, $code);
        }

        if (!$reauthenticated) {
            return $this->fail('Re-authentication failed');
        }

        $userModel->updateUser($username, [
            '2fa_secret' => null,
            '2fa_enabled' => false,
            'recovery_codes' => []
        ]);

        return $this->respond(['status' => 'success']);
    }

    public function updatePassword()
    {
        $json = $this->request->getJSON();
        $password = $json->password ?? '';
        $oldPassword = $json->old_password ?? '';

        if (!$password) return $this->fail('Password required');
        if (!$oldPassword) return $this->fail('Current password required');

        if (strlen($password) < 8) {
            return $this->fail('Password must be at least 8 characters long');
        }

        $username = session('username');
        if (!$username) return $this->failForbidden('Not logged in');

        $userModel = new UserModel();
        if (!$userModel->verifyUser($username, $oldPassword)) {
            return $this->fail('Current password is incorrect');
        }
        if ($userModel->changePassword($username, $password)) {
            if (session('force_password_change')) {
                session()->remove('force_password_change');
            }
            return $this->respond(['status' => 'success']);
        } else {
            return $this->fail('Failed to update password');
        }
    }
}
