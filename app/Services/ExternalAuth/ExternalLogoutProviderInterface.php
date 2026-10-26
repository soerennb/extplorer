<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

/** Optional capability; existing authentication adapters remain source-compatible. */
interface ExternalLogoutProviderInterface
{
    /** Return an HTTPS form target and parameters. Core always supplies the fixed return URL/state. */
    public function logoutRequest(string $returnUrl, string $state, ?ProviderSession $session): LogoutRequest;
}
