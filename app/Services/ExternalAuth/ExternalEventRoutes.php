<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;

/** Only these two exact POST shapes use authenticated server proofs instead of browser CSRF. */
final class ExternalEventRoutes
{
    public static function matches(RequestInterface $request): bool
    {
        return $request instanceof IncomingRequest && strtoupper($request->getMethod()) === 'POST'
            && preg_match('#\Aauth/(?:events/[a-z][a-z0-9_-]{0,31}|oidc/[a-z][a-z0-9_-]{0,31}/backchannel-logout)\z#', trim($request->getPath(), '/')) === 1;
    }
}
