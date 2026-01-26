<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class InstallFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if WRITEPATH is writable
        $writableError = !is_writable(WRITEPATH);
        
        // Check if users exist using UserModel
        $usersExist = false;
        try {
            $userModel = new \App\Models\UserModel();
            $users = $userModel->getUsers();
            if (!empty($users) && count($users) > 0) {
                $usersExist = true;
            }
        } catch (\Exception $e) {
            // Ignore error, assume no users
        }

        // Determine if we are currently accessing the install page
        $currentPath = trim($request->getUri()->getPath(), '/');
        $isInstallPage = ($currentPath === 'install' || strpos($currentPath, 'install/') === 0);
        $isHealthCheck = ($currentPath === 'health');

        if ($isHealthCheck) {
            return;
        }

        // If not installed (writable error OR no users)
        if ($writableError || !$usersExist) {
            if (!$isInstallPage) {
                return redirect()->to('install');
            }
        } 
        // If installed and trying to access install page
        else if ($isInstallPage) {
            return redirect()->to('/');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
