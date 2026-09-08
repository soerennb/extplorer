<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AuthenticationService;
use App\Services\LogService;
use App\Services\PasswordPolicy;

class ProfileController extends BaseController
{
    use ApiResponseTrait;

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
        session()->set('pending_2fa', [
            'secret' => $secret,
            'expires_at' => time() + 300,
        ]);

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
        $code = trim((string)($json->code ?? ''));
        $pending = session('pending_2fa');
        $secret = is_array($pending) && (int)($pending['expires_at'] ?? 0) > time()
            ? (string)($pending['secret'] ?? '')
            : '';

        if (!$secret || !$code) return $this->fail('Two-factor setup expired. Start setup again.');

        $service = new \App\Services\TwoFactorService();
        if ($service->verifyCode($secret, $code)) {
            $userModel = new UserModel();
            $recoveryCodes = $service->generateRecoveryCodes();
            
            if (!$userModel->updateUser($username, [
                '2fa_secret' => $secret,
                '2fa_enabled' => true,
                'recovery_codes' => $recoveryCodes
            ])) {
                return $this->fail('Failed to enable two-factor authentication');
            }

            session()->remove('pending_2fa');
            $updatedUser = $userModel->getUser($username);
            if ($updatedUser) {
                session()->set('auth_version', (int)$updatedUser['auth_version']);
            }
            (new AuthenticationService($userModel))->revokeUserTokens($username);
            LogService::log('Enable 2FA', '', 'Authenticator enrollment completed');
            
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

        if (!$userModel->updateUser($username, [
            '2fa_secret' => null,
            '2fa_enabled' => false,
            'recovery_codes' => []
        ])) {
            return $this->fail('Failed to disable two-factor authentication');
        }

        $updatedUser = $userModel->getUser($username);
        if ($updatedUser) {
            session()->set('auth_version', (int)$updatedUser['auth_version']);
        }
        (new AuthenticationService($userModel))->revokeUserTokens($username);
        LogService::log('Disable 2FA', '', 'Authenticator enrollment removed');

        return $this->respond(['status' => 'success']);
    }

    public function updatePassword()
    {
        $json = $this->request->getJSON();
        $password = $json->password ?? '';
        $oldPassword = $json->old_password ?? '';

        if (!$password) return $this->fail('Password required');
        if (!$oldPassword) return $this->fail('Current password required');

        $passwordError = PasswordPolicy::validate((string)$password);
        if ($passwordError !== null) {
            return $this->fail($passwordError);
        }

        $username = session('username');
        if (!$username) return $this->failForbidden('Not logged in');

        $userModel = new UserModel();
        if (!$userModel->verifyUser($username, $oldPassword)) {
            return $this->fail('Current password is incorrect', 400, 'current_password_incorrect');
        }
        if ($userModel->updateUser($username, [
            'password' => $password,
            'must_change_password' => false,
        ])) {
            $updatedUser = $userModel->getUser($username);
            if ($updatedUser) {
                session()->set('auth_version', (int)$updatedUser['auth_version']);
            }
            (new AuthenticationService($userModel))->revokeUserTokens($username);
            LogService::log('Change password', '', 'Password changed');
            if (session('force_password_change')) {
                session()->remove('force_password_change');
            }
            return $this->respond(['status' => 'success']);
        } else {
            return $this->fail('Failed to update password');
        }
    }
}
