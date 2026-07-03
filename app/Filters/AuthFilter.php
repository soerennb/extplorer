<?php

namespace App\Filters;

use App\Services\SettingsService;
use App\Services\RememberMeService;
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
                return null;
            }

            return $this->handleExpiredSession($request, false);
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
            session()->destroy();
            $remember = new RememberMeService();
            if ($remember->restore($request, Services::response())) {
                return null;
            }

            return $this->handleExpiredSession($request, true);
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
