<?php

namespace App\Controllers;

use Sabre\DAV\Server;
use Sabre\DAV\FS\Directory;
use App\Models\UserModel;
use App\Services\Dav\AuthBackend;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Exception;

class DavController extends BaseController
{
    public function index(...$path)
    {
        // 1. Setup Auth
        $authBackend = new AuthBackend();
        $authPlugin = new AuthPlugin($authBackend);

        // 2. We need to catch the "currentUser" to determine the root
        // But SabreDAV does auth during the 'start' of the server.
        // To determine the root path dynamically based on the user, 
        // we can use a custom middleware or just check Basic Auth headers manually.

        $authHeader = $this->request->getServer('HTTP_AUTHORIZATION');
        if (!$authHeader) {
            header('WWW-Authenticate: Basic realm="eXtplorer3 WebDAV"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Authentication required';
            exit;
        }

        list($user, $pass) = explode(':', base64_decode(substr($authHeader, 6)), 2);
        
        $userModel = new UserModel();
        $userData = $userModel->verifyUser($user, $pass);

        if (!$userData) {
            header('WWW-Authenticate: Basic realm="eXtplorer3 WebDAV"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Invalid credentials';
            exit;
        }

        // 3. Determine Root Path
        $baseRoot = WRITEPATH . 'file_manager_root';
        $userHome = trim($userData['home_dir'] ?? '', '/\\');
        $rootPath = $baseRoot . ( $userHome ? DIRECTORY_SEPARATOR . $userHome : '');

        if (!is_dir($rootPath)) {
            mkdir($rootPath, 0777, true);
        }

        // 4. Initialize SabreDAV
        $rootNode = new Directory($rootPath);
        $server = new Server($rootNode);

        // Set the base URL (important!)
        // If the route is /dav, set it to /dav
        $server->setBaseUri('/dav');

        // Add Auth Plugin anyway so it's compliant
        $server->addPlugin($authPlugin);

        // Add Browser Plugin (for viewing in browser)
        $server->addPlugin(new \Sabre\DAV\Browser\Plugin());

        // 5. Start Server
        $server->start();
        
        // Return empty response because SabreDAV already outputted everything
        return $this->response;
    }
}
