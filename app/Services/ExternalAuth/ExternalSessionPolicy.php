<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

/** Local account checks still run; provider sessions additionally have an absolute expiry. */
final class ExternalSessionPolicy
{
    public static function isValid(array $user, ?ExternalAuthConfiguration $configuration = null): bool
    {
        $external = session('external_auth');
        if (($user['auth_source'] ?? null) !== 'external' || !is_array($user['external_identity'] ?? null)
            || !is_array($external) || !is_int($external['expires_at'] ?? null) || $external['expires_at'] <= time()
            || ($external['identity'] ?? null) !== $user['external_identity']) return false;
        try {
            $configuration ??= new ExternalAuthConfiguration();
            return is_string($external['provider'] ?? null)
                && hash_equals($configuration->fingerprint($external['provider']), (string)($external['fingerprint'] ?? ''));
        } catch (\Throwable) {
            return false;
        }
    }
}
