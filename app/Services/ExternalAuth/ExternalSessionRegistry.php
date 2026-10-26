<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use App\Services\RemoteCredentialService;
use RuntimeException;

/** Does not depend on PHP session IDs, files, Redis keys or database session tables. */
final class ExternalSessionRegistry
{
    public function register(array $user, string $fingerprint, int $ttl, ?ProviderSession $providerSession = null, ?int $cursor = null): string
    {
        $encryptedHint = $providerSession?->idTokenHint === null ? '' : (new RemoteCredentialService())->protect($providerSession->idTokenHint);
        return ExternalState::transaction(static function (array &$documents) use ($user, $fingerprint, $ttl, $providerSession, $cursor, $encryptedHint): string {
            $state = &$documents['external_auth'];
            $key = ExternalState::identityKey($user['external_identity']);
            $current = null;
            foreach ($documents['users'] as $candidate) if ($candidate['username'] === $user['username']) $current = $candidate;
            if (!$current || !ExternalState::accountAllowed($current, $state)
                || (int)$current['auth_version'] !== (int)$user['auth_version']) throw new RuntimeException('External account changed before session registration.');
            $barrier = max($state['identities'][$key]['changed_sequence'] ?? 0,
                $state['provider_changes'][$user['external_identity']['provider']] ?? 0);
            if ($providerSession?->sid !== null) {
                $sidKey = ExternalState::sidKey($user['external_identity']['provider'], $user['external_identity']['issuer'], $providerSession->sid);
                $barrier = max($barrier, $state['sid_revocations'][$sidKey]['sequence'] ?? 0);
            }
            if ($cursor !== null && $barrier > $cursor) throw new RuntimeException('External identity was revoked while signing in.');
            $id = bin2hex(random_bytes(32));
            $state['sessions'][$id] = [
                'identity_key' => $key, 'identity' => $user['external_identity'], 'username' => $user['username'],
                'sid' => $providerSession?->sid, 'fingerprint' => $fingerprint, 'auth_version' => (int)$user['auth_version'],
                'expires_at' => time() + $ttl, 'revoked' => false, 'id_token_hint' => $encryptedHint,
            ];
            return $id;
        });
    }

    public static function valid(array $user, array $external): bool
    {
        $state = ExternalState::snapshot();
        $record = $state['sessions'][$external['registration'] ?? ''] ?? null;
        return ExternalState::accountAllowed($user, $state) && is_array($record) && empty($record['revoked'])
            && $record['expires_at'] > time() && $record['username'] === $user['username']
            && $record['identity'] === $user['external_identity']
            && $record['fingerprint'] === ($external['fingerprint'] ?? null)
            && $record['auth_version'] === (int)$user['auth_version'];
    }

    public static function revokeCurrent(): void
    {
        $id = session('external_auth')['registration'] ?? null;
        if (!is_string($id)) return;
        ExternalState::transaction(static function (array &$documents) use ($id): void {
            if (isset($documents['external_auth']['sessions'][$id])) $documents['external_auth']['sessions'][$id]['revoked'] = true;
        });
    }

    public function refreshContext(array $user, ?ProviderSession $providerSession, int $cursor): void
    {
        if ($providerSession === null) return;
        $external = session('external_auth');
        $hint = $providerSession->idTokenHint === null ? '' : (new RemoteCredentialService())->protect($providerSession->idTokenHint);
        ExternalState::transaction(static function (array &$documents) use ($user, $providerSession, $cursor, $external, $hint): void {
            $state = &$documents['external_auth'];
            $key = ExternalState::identityKey($user['external_identity']);
            $id = $external['registration'] ?? '';
            $record = $state['sessions'][$id] ?? null;
            $current = null;
            foreach ($documents['users'] as $candidate) if ($candidate['username'] === $user['username']) $current = $candidate;
            $barrier = max($state['identities'][$key]['changed_sequence'] ?? 0, $state['provider_changes'][$external['provider']] ?? 0);
            if ($providerSession->sid !== null) {
                $sidKey = ExternalState::sidKey($external['provider'], $user['external_identity']['issuer'], $providerSession->sid);
                $barrier = max($barrier, $state['sid_revocations'][$sidKey]['sequence'] ?? 0);
            }
            if (!$record || !empty($record['revoked']) || $record['expires_at'] <= time() || $barrier > $cursor
                || !$current || (int)$current['auth_version'] !== (int)$user['auth_version']
                || !ExternalState::accountAllowed($current, $state)) throw new RuntimeException('Reauthentication was revoked.');
            $state['sessions'][$id]['sid'] = $providerSession->sid;
            $state['sessions'][$id]['id_token_hint'] = $hint;
        });
    }
}
