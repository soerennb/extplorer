<?php

namespace App\Controllers;

use App\Models\UserModel;

class Login extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }
        return view('login');
    }

    public function auth()
    {
        $throttler = \Config\Services::throttler();
        if ($throttler->check(md5($this->request->getIPAddress()), 5, 60) === false) {
            return redirect()->back()->with('error', 'Too many login attempts. Please try again in a minute.');
        }

        $mode = $this->request->getPost('mode') ?? 'local';
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if ($mode === 'ftp' || $mode === 'sftp') {
            $host = $this->request->getPost('remote_host');
            $port = (int)$this->request->getPost('remote_port');
            
            try {
                if ($mode === 'ftp') {
                    new \App\Services\VFS\FtpAdapter($host, $username, $password, $port);
                } else {
                    new \App\Services\VFS\Ssh2Adapter($host, $username, $password, $port);
                }
                
                session()->regenerate();
                session()->set([
                    'isLoggedIn' => true,
                    'username' => $username,
                    'role' => 'user',
                    'home_dir' => '/',
                    'permissions' => ['read', 'write', 'upload', 'delete', 'chmod'],
                    'connection' => [
                        'mode' => $mode,
                        'host' => $host,
                        'port' => $port,
                        'user' => $username,
                        'pass' => $password
                    ]
                ]);
                return redirect()->to('/');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        $userModel = new UserModel();
        $user = $userModel->verifyUser($username, $password);

        if ($user) {
            // 2FA Check
            if (!empty($user['2fa_enabled'])) {
                $code = $this->request->getPost('2fa_code');
                if (!$code) {
                    return redirect()->back()->withInput()->with('2fa_required', true);
                }
                
                $service = new \App\Services\TwoFactorService();
                $secret = $userModel->get2faSecret($username);
                
                if (!$service->verifyCode($secret, $code)) {
                     // Check recovery codes
                     $validRecovery = false;
                     $recoveryCodes = $userModel->getRecoveryCodes($username);
                     
                     if (in_array($code, $recoveryCodes)) {
                         $validRecovery = true;
                         // Remove used code
                         $recoveryCodes = array_diff($recoveryCodes, [$code]);
                         $userModel->updateUser($username, ['recovery_codes' => array_values($recoveryCodes)]);
                     }
                     
                     if (!$validRecovery) {
                         return redirect()->back()->withInput()->with('2fa_required', true)->with('error', 'Invalid 2FA Code');
                     }
                }
            }

            $permissions = $userModel->getPermissions($username);
            session()->regenerate();
            session()->set([
                'isLoggedIn' => true,
                'username' => $user['username'],
                'role' => $user['role'],
                'home_dir' => $user['home_dir'],
                'allowed_extensions' => $user['allowed_extensions'] ?? '',
                'blocked_extensions' => $user['blocked_extensions'] ?? '',
                'permissions' => $permissions,
                'connection' => ['mode' => 'local']
            ]);
            return redirect()->to('/');
        } else {
            return redirect()->back()->with('error', 'Invalid credentials');
        }
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
