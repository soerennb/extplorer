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
            $permissions = $userModel->getPermissions($username);
            session()->set([
                'isLoggedIn' => true,
                'username' => $user['username'],
                'role' => $user['role'],
                'home_dir' => $user['home_dir'],
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
