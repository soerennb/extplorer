<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use CodeIgniter\HTTP\IncomingRequest;

interface ExternalAuthProviderInterface
{
    /** Return a verified identity or an HTTPS authorization URL. Throw on denial. */
    public function begin(IncomingRequest $request, AuthenticationContext $context): ExternalIdentity|string;

    /** Verify the website/IdP proof; the core has already checked the one-time state. */
    public function complete(IncomingRequest $request, AuthenticationContext $context): ExternalIdentity;

    public function supportsReauthentication(): bool;
}
