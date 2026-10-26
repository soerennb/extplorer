<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ExternalAuth\ExternalAuthenticationService;
use App\Services\ExternalAuth\ExternalIdentity;
use App\Services\LogService;
use App\Services\RememberMeService;

/** Public routes expose only provider IDs and one-time transactions, never arbitrary callbacks. */
final class ExternalLogin extends BaseController
{
    public function begin(string $provider)
    {
        if (($limited = $this->rateLimit()) !== null) return $limited;
        $service = null;
        $state = '';
        try {
            $service = new ExternalAuthenticationService();
            $state = $this->request->getGet('transaction');
            if ($state === null) {
                if (session('isLoggedIn')) return redirect()->to(site_url('/'));
                $return = $this->request->getGet('return');
                $state = $service->create($provider, Login::safeReturnPath(is_string($return) ? $return : Login::defaultReturnPath()))['state'];
            }
            if (!is_string($state) || preg_match('/\A[a-f0-9]{64}\z/', $state) !== 1) throw new \RuntimeException('Invalid authentication transaction.');
            $result = $service->begin($provider, $state, $this->request);
            if ($result instanceof ExternalIdentity) return $this->finish($service->complete($provider, $state, $this->request, $result));
            return redirect()->to($result)->setHeader('Cache-Control', 'no-store')->setHeader('Referrer-Policy', 'no-referrer');
        } catch (\Throwable $exception) {
            if ($service !== null && is_string($state)) $service->cancel($state);
            return $this->failed($exception);
        }
    }

    public function callback(string $provider)
    {
        if (($limited = $this->rateLimit()) !== null) return $limited;
        $service = null;
        $state = $this->request->getGet('state');
        try {
            if (!is_string($state) || preg_match('/\A[a-f0-9]{64}\z/', $state) !== 1) throw new \RuntimeException('Invalid authentication state.');
            $service = new ExternalAuthenticationService();
            return $this->finish($service->complete($provider, $state, $this->request));
        } catch (\Throwable $exception) {
            if ($service !== null && is_string($state)) $service->cancel($state);
            return $this->failed($exception);
        }
    }

    private function finish(array $result)
    {
        LogService::event('External authentication', 'success');
        $this->response->setHeader('Cache-Control', 'no-store')->setHeader('Referrer-Policy', 'no-referrer');
        if ($result['reauthentication']) return $this->response->setBody(view('external_auth_result', ['success' => true]));
        $response = redirect()->to(Login::returnUrl($result['return']))->setHeader('Cache-Control', 'no-store');
        (new RememberMeService())->forget($this->request, $response);
        return $response;
    }

    private function rateLimit()
    {
        $key = 'external-login-ip-' . hash('sha256', $this->request->getIPAddress());
        if (\Config\Services::throttler()->check($key, 20, MINUTE)) return null;
        return $this->response->setStatusCode(429)->setHeader('Retry-After', '60')
            ->setHeader('Cache-Control', 'no-store')->setBody(view('external_auth_result', ['success' => false]));
    }

    private function failed(\Throwable $exception)
    {
        // Provider exception messages can contain assertions, tokens or identity claims.
        LogService::event('External authentication', 'denied', ['failure_type' => get_debug_type($exception)]);
        return $this->response->setStatusCode(403)->setHeader('Cache-Control', 'no-store')
            ->setHeader('Referrer-Policy', 'no-referrer')->setBody(view('external_auth_result', ['success' => false]));
    }
}
