<?php

namespace App\Filters;

use App\Services\SettingsService;
use App\Services\RememberMeService;
use App\Services\RemoteEndpointPolicy;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('isLoggedIn')) {
            $remember = new RememberMeService();
            if ($remember->restore($request, Services::response())) {
                if ((bool)session('force_password_change') && !$this->isPasswordChangeRoute($request)) {
                    return $this->handlePasswordChangeRequired($request);
                }
                return null;
            }

            return $this->handleExpiredSession($request, false);
        }

        if (!$this->isCurrentSessionValid()) {
            $this->terminateSession($request);
            return $this->handleExpiredSession($request, true);
        }

        $settingsService = new SettingsService();
        $timeoutMinutes = (int)$settingsService->get('session_idle_timeout_minutes', 0);
        if ($timeoutMinutes <= 0) {
            $this->touchLastActivity();
            return null;
        }

        $now = time();
        $lastActivity = (int)(session('last_activity_ts') ?? 0);
        $timeoutSeconds = $timeoutMinutes * 60;

        if ($lastActivity > 0 && ($now - $lastActivity) > $timeoutSeconds) {
            $this->terminateSession($request);
            return $this->handleExpiredSession($request, true);
        }

        if ((bool)session('force_password_change') && !$this->isPasswordChangeRoute($request)) {
            return $this->handlePasswordChangeRequired($request);
        }

        $this->touchLastActivity();
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing here
    }

    private function touchLastActivity(): void
    {
        session()->set('last_activity_ts', time());
    }

    private function isCurrentSessionValid(): bool
    {
        $connection = session('connection');
        if (($connection['mode'] ?? 'local') !== 'local') {
            try {
                if (!empty($connection['direct_login'])) {
                    (new RemoteEndpointPolicy())->assertDirectLoginEnabled();
                }
                (new RemoteEndpointPolicy())->authorize(
                    (string)$connection['mode'],
                    (string)($connection['host'] ?? ''),
                    (int)($connection['port'] ?? 0),
                    (string)($connection['host_key_fingerprint'] ?? ''),
                    (string)($connection['mode'] ?? '') === 'ftps' && !empty($connection['tls_verified'])
                );
                return true;
            } catch (\Throwable $exception) {
                log_message('warning', 'Remote session rejected by current endpoint policy: {message}', [
                    'message' => $exception->getMessage(),
                ]);
                return false;
            }
        }

        $username = (string)session('username');
        if ($username === '') {
            return false;
        }

        $user = (new UserModel())->getUser($username);
        if (!$user || !empty($user['disabled']) || (int)($user['locked_until'] ?? 0) > time()) {
            return false;
        }

        $sessionVersion = (int)session('auth_version');
        $currentVersion = (int)($user['auth_version'] ?? 1);
        if ($sessionVersion <= 0) {
            session()->set('auth_version', $currentVersion);
            return true;
        }

        return $sessionVersion === $currentVersion;
    }

    private function terminateSession(RequestInterface $request): void
    {
        $remember = new RememberMeService();
        $remember->forget($request, Services::response());
        session()->remove([
            'isLoggedIn',
            'username',
            'role',
            'home_dir',
            'permissions',
            'connection',
            'auth_version',
            'force_password_change',
            'remembered_login',
            'last_activity_ts',
        ]);
        session()->destroy();
    }

    private function handleExpiredSession(RequestInterface $request, bool $expired)
    {
        $path = '';
        $query = '';
        if ($request instanceof IncomingRequest) {
            $uri = $request->getUri();
            $path = trim($uri->getPath(), '/');
            $query = $uri->getQuery();
        }

        if (str_starts_with($path, 'api/') || $this->expectsJson($request)) {
            $response = Services::response();
            $response->setStatusCode(401);
            $response->setJSON([
                'status' => 'error',
                'code' => $expired ? 'session_expired' : 'auth_required',
                'message' => $expired ? 'Session expired due to inactivity.' : 'Authentication required.',
                'login_url' => $this->loginUrl($expired, $this->returnPath($path, $query)),
                'return_url' => $this->returnPath($path, $query),
            ]);
            return $response;
        }

        return redirect()->to($this->loginUrl($expired, $this->returnPath($path, $query)));
    }

    private function expectsJson(RequestInterface $request): bool
    {
        if (!$request instanceof IncomingRequest) {
            return false;
        }

        $requestedWith = strtolower((string)$request->getHeaderLine('X-Requested-With'));
        if ($requestedWith === 'xmlhttprequest') {
            return true;
        }

        $accept = strtolower((string)$request->getHeaderLine('Accept'));
        return str_contains($accept, 'application/json') || str_contains($accept, 'text/json');
    }

    private function isPasswordChangeRoute(RequestInterface $request): bool
    {
        $path = trim($request->getUri()->getPath(), '/');
        return $path === ''
            || $path === 'api/profile/details'
            || $path === 'api/profile/password'
            || $path === 'logout';
    }

    private function handlePasswordChangeRequired(RequestInterface $request)
    {
        if ($this->expectsJson($request) || str_starts_with(trim($request->getUri()->getPath(), '/'), 'api/')) {
            return Services::response()
                ->setStatusCode(403)
                ->setJSON([
                    'status' => 'error',
                    'code' => 'password_change_required',
                    'message' => 'Password change is required before continuing.',
                ]);
        }

        return redirect()->to(site_url('/?password_change_required=1'));
    }

    private function returnPath(string $path, string $query = ''): string
    {
        $return = '/' . ltrim($path, '/');
        if ($return === '/') {
            return '/';
        }

        return $query !== '' ? $return . '?' . $query : $return;
    }

    private function loginUrl(bool $expired, string $returnPath): string
    {
        $params = ['return' => $returnPath];
        if ($expired) {
            $params['expired'] = '1';
        }

        return site_url('login') . '?' . http_build_query($params);
    }
}
