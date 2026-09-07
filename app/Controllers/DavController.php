<?php

namespace App\Controllers;

use Sabre\DAV\Server;
use App\Services\Dav\SafeDirectory;
use App\Models\UserModel;
use App\Services\Dav\AuthBackend;
use App\Services\VFS\PathPolicy;
use App\Services\LogService;
use App\Services\ResourcePolicy;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Exception;

class DavController extends BaseController
{
    /** @var list<string> */
    private array $allowedMethods = [
        'OPTIONS', 'GET', 'HEAD', 'PUT', 'DELETE', 'MKCOL', 'MOVE', 'COPY',
        'PROPFIND', 'PROPPATCH', 'LOCK', 'UNLOCK',
    ];
    /**
     * Minimal permissions required for WebDAV to avoid bypassing UI/API permission controls.
     *
     * @var array<int, string>
     */
    private array $requiredDavPermissions = ['read', 'write', 'upload', 'delete', 'rename'];

    public function index(...$path)
    {
        $method = strtoupper($this->request->getMethod());
        if (!in_array($method, $this->allowedMethods, true)) {
            return $this->denyDav(405, 'WebDAV method is not allowed.', $method);
        }

        if (!$this->requestLimitsAllow($method)) {
            return $this->response;
        }

        // 0. Global Switch
        $settings = new \App\Services\SettingsService();
        if (!$settings->get('webdav_enabled', true)) {
            return $this->denyDav(403, 'WebDAV access is disabled.', 'disabled');
        }

        // 1. Security Check: Rate Limiting
        $throttler = \Config\Services::throttler();
        $rate = in_array($method, ['PUT', 'DELETE', 'MKCOL', 'MOVE', 'COPY', 'PROPPATCH', 'LOCK', 'UNLOCK'], true) ? 60 : 120;
        $rateKey = 'dav-' . $method . '-' . hash('sha256', $this->request->getIPAddress());
        if ($throttler->check($rateKey, $rate, MINUTE) === false) {
            return $this->denyDav(429, 'Too many requests.', 'rate-limit');
        }

        // 2. Security Check: HTTPS Enforcement (Optional but recommended)
        // If not already handled by a global filter
        if (ENVIRONMENT !== 'development' && !$this->request->isSecure()) {
            return $this->denyDav(403, 'HTTPS is required for WebDAV.', 'https-required');
        }

        // 3. Setup Auth Backend
        $authBackend = new AuthBackend();

        // 4. Determine User and Root Path BEFORE starting SabreDAV
        $authHeader = $this->request->getServer('HTTP_AUTHORIZATION');
        $userData = null;

        if (is_string($authHeader) && strlen($authHeader) > 8192) {
            return $this->denyDav(400, 'Invalid WebDAV authentication header.', 'auth-header-size');
        }

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
            LogService::log('WebDAV Auth Required', 'dav', 'Missing or invalid credentials', 'Public');
            return $this->response
                ->setStatusCode(401)
                ->setHeader('WWW-Authenticate', 'Basic realm="eXtplorer3 WebDAV"')
                ->setBody('Authentication required.');
        }

        // 3. Determine Root Path
        $baseRoot = config('Storage')->fileManagerRoot;
        $rootPath = $this->resolveSafeDavRootPath($baseRoot, (string)($userData['home_dir'] ?? '/'));

        if (!is_dir($rootPath)) {
            mkdir($rootPath, 0755, true);
        }

        // 4. Initialize SabreDAV
        $rootNode = new SafeDirectory($rootPath, $rootPath);
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
        $davCacheDir = config('Storage')->cache . '/dav';
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

    private function requestLimitsAllow(string $method): bool
    {
        $policy = new ResourcePolicy();
        $contentLength = trim($this->request->getHeaderLine('Content-Length'));
        if ($contentLength !== '' && (!ctype_digit($contentLength) || (int)$contentLength > $this->maxDavBodyBytes())) {
            $this->response = $this->denyDav(413, 'WebDAV request body exceeds the configured limit.', 'body-size');
            return false;
        }

        $depth = strtolower(trim($this->request->getHeaderLine('Depth')));
        $maxDepth = $policy->configuredInteger('EXTPLORER_WEBDAV_MAX_DEPTH', 1, 0, 10);
        if ($depth === 'infinity' || ($depth !== '' && ctype_digit($depth) && (int)$depth > $maxDepth)) {
            $this->response = $this->denyDav(413, 'WebDAV request depth exceeds the configured limit.', 'depth');
            return false;
        }
        if ($depth !== '' && $depth !== 'infinity' && !ctype_digit($depth)) {
            $this->response = $this->denyDav(400, 'Invalid WebDAV Depth header.', 'depth-header');
            return false;
        }

        foreach (['If', 'Destination'] as $header) {
            if (strlen($this->request->getHeaderLine($header)) > 8192) {
                $this->response = $this->denyDav(400, 'WebDAV request header is too large.', strtolower($header));
                return false;
            }
        }

        $destination = trim($this->request->getHeaderLine('Destination'));
        if ($destination !== '') {
            $parsed = parse_url($destination);
            if (!is_array($parsed)
                || !isset($parsed['path'])
                || !str_starts_with((string)$parsed['path'], '/')
                || isset($parsed['user'], $parsed['pass'], $parsed['query'], $parsed['fragment'])) {
                $this->response = $this->denyDav(400, 'Invalid WebDAV destination.', 'destination');
                return false;
            }

            if (isset($parsed['host'])) {
                $requestUri = $this->request->getUri();
                $destinationHost = strtolower((string)$parsed['host']);
                $requestHost = strtolower($requestUri->getHost());
                $destinationPort = isset($parsed['port']) ? (int)$parsed['port'] : null;
                if ($destinationHost !== $requestHost
                    || ($destinationPort !== null && $destinationPort !== $requestUri->getPort())) {
                    $this->response = $this->denyDav(400, 'Cross-origin WebDAV destinations are not allowed.', 'destination-origin');
                    return false;
                }
            }
        }

        return true;
    }

    private function maxDavBodyBytes(): int
    {
        $settings = (new \App\Services\SettingsService())->getSettings();
        $maxMb = (int)($settings['upload_max_file_mb'] ?? 100);
        return max(1, $maxMb) * 1024 * 1024;
    }

    private function denyDav(int $status, string $message, string $reason)
    {
        LogService::log('WebDAV Request Denied', 'dav', $reason, 'Public');
        $response = $this->response->setStatusCode($status)->setBody($message);
        if ($status === 405) {
            $response->setHeader('Allow', implode(', ', $this->allowedMethods));
        }
        return $response;
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
        try {
            return PathPolicy::resolve($baseRoot, $homeDir);
        } catch (\Throwable $e) {
            throw new Exception('Invalid WebDAV home directory.', 0, $e);
        }
    }
}
