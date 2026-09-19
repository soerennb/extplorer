<?php

namespace App\Controllers;

use App\Services\LogService;
use App\Services\StepUpAuthenticationService;
use Config\Services;

final class StepUpController extends BaseController
{
    use ApiResponseTrait;

    public function create()
    {
        $json = $this->request->getJSON(true);
        $action = is_array($json) ? trim((string)($json['action'] ?? '')) : '';
        $password = is_array($json) ? (string)($json['password'] ?? '') : '';

        if (!in_array($action, StepUpAuthenticationService::ACTIONS, true) || $password === '') {
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
            $token = (new StepUpAuthenticationService())->issue($action, $password);
            LogService::log('Step-up authentication', '', 'Sensitive action authorized');
            return $this->respond(['status' => 'success', 'token' => $token]);
        } catch (\Throwable) {
            LogService::log('Step-up authentication failed', '', 'Sensitive action rejected');
            return $this->fail('Re-authentication failed.', 403);
        }
    }
}
