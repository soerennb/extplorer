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
        // We can't really do much if it's not, but let's try to handle it.
        $writableError = !is_writable(WRITEPATH) || !is_writable(WRITEPATH . 'users.json');
        
        // Check if users exist
        $usersExist = false;
        $usersFile = WRITEPATH . 'users.json';
        if (file_exists($usersFile)) {
            $json = json_decode(file_get_contents($usersFile), true);
            if (!empty($json) && is_array($json) && count($json) > 0) {
                $usersExist = true;
            }
        }

        // Determine if we are currently accessing the install page
        $currentPath = $request->getUri()->getPath();
        $isInstallPage = strpos($currentPath, 'install') === 0;

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
