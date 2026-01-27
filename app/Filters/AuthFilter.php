<?php

namespace App\Filters;

use App\Services\SettingsService;
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
            return redirect()->to('/login');
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
            return $this->handleExpiredSession($request);
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

    private function handleExpiredSession(RequestInterface $request)
    {
        $path = '';
        if ($request instanceof IncomingRequest) {
            $path = trim($request->getUri()->getPath(), '/');
        }

        if (str_starts_with($path, 'api/')) {
            $response = Services::response();
            $response->setStatusCode(401);
            $response->setJSON([
                'status' => 'error',
                'messages' => [
                    'error' => 'Session expired due to inactivity.',
                ],
            ]);
            return $response;
        }

        return redirect()->to('/login?expired=1');
    }
}
