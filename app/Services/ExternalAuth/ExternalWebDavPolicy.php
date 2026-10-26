<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

final class ExternalWebDavPolicy
{
    public static function forgetRemovedProviders(array $configured): void
    {
        foreach (ExternalState::snapshot()['providers'] as $id => $fingerprint) {
            if (!array_key_exists($id, $configured)) self::observe($id, 'removed');
        }
    }

    public static function forUser(array $user): array
    {
        if (($user['auth_source'] ?? 'local') !== 'external') return ['enabled' => true, 'ttl' => null, 'fingerprint' => null];
        try {
            $configuration = new ExternalAuthConfiguration();
            $providerId = $user['external_identity']['provider'];
            $provider = $configuration->provider($providerId);
            $fingerprint = $configuration->fingerprint($providerId);
            self::observe($providerId, $fingerprint);
            return ['enabled' => ($provider['webdav_enabled'] ?? false) === true && ExternalState::accountAllowed($user),
                'ttl' => $provider['webdav_ttl_seconds'] ?? 2592000, 'fingerprint' => $fingerprint];
        } catch (\Throwable) {
            return ['enabled' => false, 'ttl' => null, 'fingerprint' => null];
        }
    }

    /** Called on readiness and external capability reads, not inside another state transaction. */
    public static function observe(string $provider, string $fingerprint): void
    {
        $previous = ExternalState::snapshot()['providers'][$provider] ?? null;
        if ($previous === $fingerprint) return;
        ExternalState::transaction(static function (array &$documents) use ($provider, $fingerprint): void {
            $previous = $documents['external_auth']['providers'][$provider] ?? null;
            if ($previous !== null && $previous !== $fingerprint) {
                $documents['external_auth']['sequence']++;
                $documents['external_auth']['provider_changes'][$provider] = $documents['external_auth']['sequence'];
                foreach ($documents['external_auth']['sessions'] as &$session) {
                    if ($session['identity']['provider'] === $provider) $session['revoked'] = true;
                }
                unset($session);
                $documents['webdav_credentials'] = array_values(array_filter($documents['webdav_credentials'],
                    static fn(array $entry): bool => ($entry['external_identity']['provider'] ?? null) !== $provider));
            }
            $documents['external_auth']['providers'][$provider] = $fingerprint;
        });
    }
}
