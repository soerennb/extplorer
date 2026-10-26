<?php

declare(strict_types=1);

namespace ExtplorerAuth;

use App\Services\ExternalAuth\AuthenticationContext;
use App\Services\ExternalAuth\ExternalAuthProviderInterface;
use App\Services\ExternalAuth\ExternalIdentity;
use CodeIgniter\HTTP\IncomingRequest;

final class FixtureProvider implements ExternalAuthProviderInterface
{
    public static ExternalIdentity $identity;
    public static bool $reauthentication = true;

    public function begin(IncomingRequest $request, AuthenticationContext $context): string
    {
        return 'https://website.test/login?state=' . $context->state;
    }

    public function complete(IncomingRequest $request, AuthenticationContext $context): ExternalIdentity
    {
        return self::$identity;
    }

    public function supportsReauthentication(): bool { return self::$reauthentication; }
}
