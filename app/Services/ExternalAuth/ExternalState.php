<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use App\Services\AtomicFileStore;
use App\Services\AuthorizationState;

/** Shared metadata boundary for account state, sessions and external credentials. */
final class ExternalState
{
    public static function identityKey(array $identity): string
    {
        return hash('sha256', json_encode([$identity['provider'], $identity['issuer'], $identity['subject']], JSON_THROW_ON_ERROR));
    }

    public static function sidKey(string $provider, string $issuer, string $sid): string
    {
        return hash('sha256', json_encode([$provider, $issuer, $sid], JSON_THROW_ON_ERROR));
    }

    public static function defaults(): array
    {
        return ['sequence' => 0, 'identities' => [], 'sessions' => [], 'sid_revocations' => [], 'replays' => [], 'logouts' => [], 'providers' => [], 'provider_changes' => []];
    }

    public static function snapshot(): array
    {
        return AtomicFileStore::read(config('Storage')->state . '/external_auth.php', self::defaults());
    }

    /** Callback receives logical document names, never backend-specific paths. */
    public static function transaction(callable $callback): mixed
    {
        $root = config('Storage')->state;
        $defaults = ['users' => [], 'roles' => AuthorizationState::DEFAULT_ROLES, 'groups' => [],
            'external_auth' => self::defaults(), 'webdav_credentials' => []];
        $paths = [];
        foreach ($defaults as $name => $default) $paths[$root . '/' . $name . '.php'] = $default;
        return AtomicFileStore::transactionMany($paths, static function (array &$documents) use ($root, $defaults, $callback): mixed {
            $state = [];
            foreach ($defaults as $name => $default) $state[$name] = $documents[$root . '/' . $name . '.php'];
            $state['external_auth'] += self::defaults();
            $state['roles'] = $state['roles'] ?: AuthorizationState::DEFAULT_ROLES;
            $result = $callback($state);
            foreach ($defaults as $name => $default) $documents[$root . '/' . $name . '.php'] = $state[$name];
            return $result;
        });
    }

    /** A lock is identity-scoped, so deleting/recreating a local account cannot bypass it. */
    public static function accountAllowed(array $user, ?array $state = null): bool
    {
        if (!empty($user['disabled'])) return false;
        if (($user['auth_source'] ?? 'local') !== 'external') return true;
        if (!is_array($user['external_identity'] ?? null)) return false;
        $state ??= self::snapshot();
        $identity = $state['identities'][self::identityKey($user['external_identity'])] ?? [];
        return empty($identity['suspended']) && ($identity['admitted'] ?? true) === true;
    }

    public static function cleanup(): array
    {
        return self::transaction(static function (array &$documents): array {
            $state = &$documents['external_auth'];
            $stats = [];
            foreach (['sessions', 'sid_revocations', 'replays', 'logouts'] as $section) {
                $before = count($state[$section]);
                $state[$section] = array_filter($state[$section], static fn(array $entry): bool => ($entry['expires_at'] ?? 0) > time());
                $stats[$section] = $before - count($state[$section]);
            }
            // Identity status/revisions and barriers deliberately survive cleanup.
            return $stats;
        });
    }
}
