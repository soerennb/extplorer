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
    /**
     * Minimal permissions required for WebDAV to avoid bypassing UI/API permission controls.
     *
     * @var array<int, string>
     */
    private array $requiredDavPermissions = ['read', 'write', 'upload', 'delete', 'rename'];

    public function index(...$path)
    {
        // 0. Global Switch
        $settings = new \App\Services\SettingsService();
        if (!$settings->get('webdav_enabled', true)) {
            header('HTTP/1.1 403 Forbidden');
            echo 'WebDAV Access is disabled by the administrator.';
            exit;
        }

        // 1. Security Check: Rate Limiting
        $throttler = \Config\Services::throttler();
        if ($throttler->check('dav-' . $this->request->getIPAddress(), 120, MINUTE) === false) {
            header('HTTP/1.1 429 Too Many Requests');
            echo 'Too many requests. Please slow down.';
            exit;
        }

        // 2. Security Check: HTTPS Enforcement (Optional but recommended)
        // If not already handled by a global filter
        if (ENVIRONMENT !== 'development' && !$this->request->isSecure()) {
            header('HTTP/1.1 403 Forbidden');
            echo 'SSL/HTTPS is required for WebDAV.';
            exit;
        }

        // 3. Setup Auth Backend
        $authBackend = new AuthBackend();

        // 4. Determine User and Root Path BEFORE starting SabreDAV
        $authHeader = $this->request->getServer('HTTP_AUTHORIZATION');
        $userData = null;

        if ($authHeader && stripos($authHeader, 'Basic ') === 0) {
            $credentials = base64_decode(substr($authHeader, 6), true);
            if (is_string($credentials) && str_contains($credentials, ':')) {
                [$user, $pass] = explode(':', $credentials, 2);
                $userModel = new UserModel();
                $userData = $userModel->verifyUser($user, $pass);
                
                if (!$userData) {
                    \App\Services\LogService::log('WebDAV Auth Failed', 'dav', "Failed login attempt", $user);
                } else {
                    if (!$this->hasRequiredDavPermissions($userModel, (string)$userData['username'])) {
                        \App\Services\LogService::log('WebDAV Access Forbidden', 'dav', "Insufficient permissions", (string)$userData['username']);
                        $userData = null;
                    }
                    // Optional: Log successful logins (might be noisy)
                    // \App\Services\LogService::log('WebDAV Login', 'dav', "Successful login", $user);
                }
            }
        }

        if (!$userData) {
            header('WWW-Authenticate: Basic realm="eXtplorer3 WebDAV"');
            header('HTTP/1.1 401 Unauthorized');
            echo 'Authentication required';
            exit;
        }

        // 3. Determine Root Path
        $baseRoot = WRITEPATH . 'file_manager_root';
        $rootPath = $this->resolveSafeDavRootPath($baseRoot, (string)($userData['home_dir'] ?? '/'));

        if (!is_dir($rootPath)) {
            mkdir($rootPath, 0755, true);
        }

        // 4. Initialize SabreDAV
        $rootNode = new Directory($rootPath);
        $server = new Server($rootNode);

        // Set the base URL (important!)
        // Determine base URI dynamically using site_url
        $baseUri = parse_url(site_url('dav'), PHP_URL_PATH);
        if (!$baseUri) $baseUri = '/dav';
        $server->setBaseUri($baseUri);

        // Add Auth Plugin
        $authPlugin = new AuthPlugin($authBackend);
        $server->addPlugin($authPlugin);

        // Add Browser Plugin (for viewing in browser)
        $server->addPlugin(new \Sabre\DAV\Browser\Plugin());

        // Add Locks Plugin (Essential for Windows/macOS clients)
        $davCacheDir = WRITEPATH . 'cache/dav';
        if (!is_dir($davCacheDir)) {
            mkdir($davCacheDir, 0755, true);
        }

        $locksBackend = new \Sabre\DAV\Locks\Backend\File($davCacheDir . '/locks');
        $server->addPlugin(new \Sabre\DAV\Locks\Plugin($locksBackend));

        // Add Temporary File Filter (to hide .DS_Store, etc.)
        $server->addPlugin(new \Sabre\DAV\TemporaryFileFilterPlugin($davCacheDir . '/temp'));

        // 5. Start Server
        $server->start();
        
        // Return empty response because SabreDAV already outputted everything
        return $this->response;
    }

    private function hasRequiredDavPermissions(UserModel $userModel, string $username): bool
    {
        $permissions = $userModel->getPermissions($username);
        if (in_array('*', $permissions, true)) {
            return true;
        }

        foreach ($this->requiredDavPermissions as $permission) {
            if (!in_array($permission, $permissions, true)) {
                return false;
            }
        }

        return true;
    }

    private function resolveSafeDavRootPath(string $baseRoot, string $homeDir): string
    {
        $baseRoot = rtrim((string)realpath($baseRoot), DIRECTORY_SEPARATOR);
        if ($baseRoot === '') {
            throw new Exception('Invalid WebDAV base root.');
        }

        $home = str_replace('..', '', $homeDir);
        $home = trim($home, "/\\");

        $rootPath = $baseRoot;
        if ($home !== '') {
            $rootPath .= DIRECTORY_SEPARATOR . $home;
        }

        $rootReal = realpath($rootPath);
        if ($rootReal === false) {
            return $rootPath;
        }

        $basePrefix = $baseRoot . DIRECTORY_SEPARATOR;
        $resolvedPrefix = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($rootReal !== $baseRoot && !str_starts_with($resolvedPrefix, $basePrefix)) {
            throw new Exception('Invalid WebDAV home directory.');
        }

        return $rootReal;
    }
}
