<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

/** Core-owned transaction data. Providers must bind their proof to this transaction. */
final class AuthenticationContext
{
    public function __construct(
        public readonly string $callbackUrl,
        public readonly string $state,
        public readonly string $nonce,
        public readonly string $codeVerifier,
        public readonly int $startedAt,
        public readonly bool $reauthentication = false,
    ) {}
}
