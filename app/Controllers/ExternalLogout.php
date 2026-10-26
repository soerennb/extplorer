<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthenticationService;
use App\Services\ExternalAuth\ExternalLogoutService;
use App\Services\LogService;

final class ExternalLogout extends BaseController
{
    public function begin()
    {
        $prepared = null;
        try {
            // Prepare local hints before destroying the session. The provider request is only
            // handed to the browser after local logout, including when discovery has failed.
            $prepared = (new ExternalLogoutService())->prepare();
        } catch (\Throwable) {
            LogService::event('External provider logout', 'unavailable');
        }
        (new AuthenticationService())->logout($this->request, $this->response);
        $this->response->setHeader('Cache-Control', 'no-store')->setHeader('Referrer-Policy', 'no-referrer');
        if ($prepared === null) {
            return $this->response->setBody(view('external_auth_result', ['success' => false, 'logout_failed' => true]));
        }
        try {
            $request = $prepared['provider']->logoutRequest(site_url('login/external-logout/callback'), $prepared['state'], $prepared['context']);
        } catch (\Throwable) {
            return $this->response->setBody(view('external_auth_result', ['success' => false, 'logout_failed' => true]));
        }
        $this->response->setCookie(ExternalLogoutService::COOKIE_NAME, $prepared['cookie'], 300, '', '/', '', $this->request->isSecure(), true, 'Lax');
        // Restrict this single handoff page's form action to the verified provider origin.
        service('csp')->addFormAction(\App\Services\ExternalAuth\RestrictedHttpClient::origin($request->url));
        return $this->response->setBody(view('external_logout', ['logout_request' => $request]));
    }

    public function callback()
    {
        $state = $this->request->getGet('state');
        $cookie = $this->request->getCookie(ExternalLogoutService::COOKIE_NAME);
        $valid = is_string($state) && is_string($cookie) && (new ExternalLogoutService())->complete($state, $cookie);
        $this->response->deleteCookie(ExternalLogoutService::COOKIE_NAME);
        if (!$valid) return $this->response->setStatusCode(400)->setHeader('Cache-Control', 'no-store')->setBody(view('external_auth_result', ['success' => false, 'logout_failed' => true]));
        LogService::event('External provider logout', 'returned', [], 'System');
        return redirect()->to(site_url('login') . '?logged_out=1')->deleteCookie(ExternalLogoutService::COOKIE_NAME)->setHeader('Cache-Control', 'no-store');
    }
}
