<?php

namespace App\Controllers;

use App\Services\LogService;
use App\Services\StepUpAuthenticationService;
use Config\Services;
use App\Services\SessionAccount;
use App\Services\ExternalAuth\ExternalAuthenticationService;

final class StepUpController extends BaseController
{
    use ApiResponseTrait;

    public function create()
    {
        $json = $this->request->getJSON(true);
        foreach (['action', 'password', 'code'] as $field) {
            if (is_array($json) && array_key_exists($field, $json) && !is_string($json[$field])) {
                return $this->fail('Re-authentication request is invalid.', 422);
            }
        }
        $action = is_array($json) ? trim((string)($json['action'] ?? '')) : '';
        $password = is_array($json) ? (string)($json['password'] ?? '') : '';
        $code = is_array($json) ? trim((string)($json['code'] ?? '')) : '';

        if (!in_array($action, StepUpAuthenticationService::ACTIONS, true) || (!SessionAccount::isExternal() && $password === '')) {
            return $this->fail('Re-authentication request is invalid.', 422);
        }

        $username = strtolower(trim((string) Services::session()->get('username')));
        $throttleKey = 'step-up-' . hash(
            'sha256',
            $username . '|' . $this->request->getIPAddress()
        );
        if (\Config\Services::throttler()->check($throttleKey, 10, MINUTE) === false) {
            LogService::log('Step-up authentication throttled', '', 'Sensitive action rate limit exceeded');
            return $this->fail('Too many re-authentication attempts. Please try again later.', 429);
        }

        try {
            if (SessionAccount::isExternal()) {
                $provider = (string)session('external_auth')['provider'];
                $transaction = (new ExternalAuthenticationService())->create($provider, site_url('/'), true);
                $this->response->setHeader('Cache-Control', 'no-store');
                return $this->respond([
                    'method' => 'external', 'challenge' => $transaction['state'],
                    'exchange_secret' => $transaction['exchange_secret'],
                    'start_url' => site_url('login/external/' . $provider) . '?' . http_build_query(['transaction' => $transaction['state']]),
                ]);
            }
            $token = (new StepUpAuthenticationService())->issue($action, $password, $code);
            LogService::log('Step-up authentication', '', 'Sensitive action authorized');
            return $this->respond(['status' => 'success', 'token' => $token]);
        } catch (\Throwable) {
            LogService::log('Step-up authentication failed', '', 'Sensitive action rejected');
            return $this->fail('Re-authentication failed.', 403);
        }
    }

    public function exchange()
    {
        $json = $this->request->getJSON(true);
        if (!is_array($json) || !is_string($json['challenge'] ?? null) || !is_string($json['exchange_secret'] ?? null)
            || preg_match('/\A[a-f0-9]{64}\z/', $json['challenge']) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/', $json['exchange_secret']) !== 1) return $this->fail(lang('ExternalAuth.failed'), 422);
        $this->response->setHeader('Cache-Control', 'no-store');
        try {
            $token = (new ExternalAuthenticationService())->exchange($json['challenge'], $json['exchange_secret']);
            if ($token === null) return $this->respond(['status' => 'pending'], 202);
            LogService::event('External step-up', 'success');
            return $this->respond(['token' => $token]);
        } catch (\Throwable) {
            return $this->fail(lang('ExternalAuth.failed'), 403);
        }
    }

    public function cancel()
    {
        $json = $this->request->getJSON(true);
        if (!is_array($json) || !is_string($json['challenge'] ?? null)
            || preg_match('/\A[a-f0-9]{64}\z/', $json['challenge']) !== 1) return $this->fail(lang('ExternalAuth.failed'), 422);
        try {
            (new ExternalAuthenticationService())->cancel($json['challenge']);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable) {
            return $this->fail(lang('ExternalAuth.failed'), 403);
        }
    }
}
