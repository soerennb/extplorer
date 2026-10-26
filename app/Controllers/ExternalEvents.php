<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ExternalAuth\ExternalAuthConfiguration;
use App\Services\ExternalAuth\ExternalLifecycleService;
use App\Services\ExternalAuth\OidcProvider;
use App\Services\ExternalAuth\SignedExternalEvents;
use App\Services\LogService;

final class ExternalEvents extends BaseController
{
    public function receive(string $provider) { return $this->handle($provider, false); }
    public function backchannel(string $provider) { return $this->handle($provider, true); }

    private function handle(string $provider, bool $oidc)
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        if (strlen((string)$this->request->getBody()) > 65536) return $this->response->setStatusCode(413)->setJSON(['error' => 'request_too_large']);
        $key = 'external-event-' . hash('sha256', $provider . '|' . $this->request->getIPAddress());
        if (!\Config\Services::throttler()->check($key, 120, MINUTE)) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', '60')->setJSON(['error' => 'rate_limited']);
        }
        try {
            $type = strtolower(explode(';', $this->request->getHeaderLine('Content-Type'), 2)[0]);
            if ($oidc) {
                if ($type !== 'application/x-www-form-urlencoded') throw new \RuntimeException('Invalid event content type.');
                $instance = (new ExternalAuthConfiguration())->createProvider($provider);
                $token = $this->request->getPost('logout_token');
                if (!$instance instanceof OidcProvider || !is_string($token)) throw new \RuntimeException('Invalid logout request.');
                $claims = $instance->verifyLogoutToken($token);
                (new ExternalLifecycleService())->sessionsRevoked($provider, $claims['iss'], $claims['sub'] ?? null,
                    $claims['sid'] ?? null, $claims['jti'], $claims['exp']);
            } else {
                if ($type !== 'application/json') throw new \RuntimeException('Invalid event content type.');
                (new SignedExternalEvents())->receive($provider, (string)$this->request->getBody(),
                    $this->request->getHeaderLine('X-Extplorer-Event-Time'), $this->request->getHeaderLine('X-Extplorer-Event-Signature'));
            }
            LogService::event($oidc ? 'OIDC backchannel logout' : 'External lifecycle event', 'success', ['provider' => $provider], 'System');
            return $this->response->setStatusCode(200)->setJSON(['status' => 'accepted']);
        } catch (\Throwable $exception) {
            LogService::event('External lifecycle event', 'denied', ['provider' => $provider, 'failure_type' => get_debug_type($exception)], 'System');
            return $this->response->setStatusCode(400)->setJSON(['error' => 'invalid_event']);
        }
    }
}
