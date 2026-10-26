<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use InvalidArgumentException;

/** Verified, server-only provider context; never exposed in profile/API responses. */
final class ProviderSession
{
    public function __construct(public readonly ?string $sid = null, public readonly ?string $idTokenHint = null)
    {
        if (($sid !== null && ($sid === '' || strlen($sid) > 255 || preg_match('/[\x00-\x1f\x7f]/', $sid)))
            || ($idTokenHint !== null && strlen($idTokenHint) > 32768)) throw new InvalidArgumentException('Invalid provider session.');
    }
}
