<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use InvalidArgumentException;

/** A provider's verified identity, never a browser-supplied authorization record. */
final class ExternalIdentity
{
    /** @param array<array-key, mixed> $groups Validated at the provider boundary. */
    public function __construct(
        public readonly string $issuer,
        public readonly string $subject,
        public readonly string $displayName,
        public readonly array $groups = [],
        public readonly ?int $authenticatedAt = null,
    ) {
        if ($issuer === '' || strlen($issuer) > 2048 || $subject === '' || strlen($subject) > 255
            || strlen($displayName) > 255 || count($groups) > 256 || !array_is_list($groups)
            || preg_match('/[\x00-\x1f\x7f]/', $issuer . $subject . $displayName)
            || array_filter($groups, static fn($group): bool => !is_string($group) || strlen($group) > 255) !== []) {
            throw new InvalidArgumentException('Invalid external identity.');
        }
    }

    /** @return array{provider: string, issuer: string, subject: string} */
    public function key(string $provider): array
    {
        return ['provider' => $provider, 'issuer' => $this->issuer, 'subject' => $this->subject];
    }
}
