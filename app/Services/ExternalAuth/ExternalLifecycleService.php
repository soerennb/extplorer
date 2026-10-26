<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use RuntimeException;

/** Applies already authenticated events atomically with users and their authorization state. */
final class ExternalLifecycleService
{
    public function accountUpdated(string $providerId, array $provider, array $event): void
    {
        $identity = new ExternalIdentity($provider['issuer'], $event['sub'], '', $event['groups']);
        $key = $identity->key($providerId);
        ExternalState::transaction(static function (array &$documents) use ($provider, $event, $identity, $key): void {
            $state = &$documents['external_auth'];
            $identityKey = ExternalState::identityKey($key);
            $previous = $state['identities'][$identityKey] ?? [];
            $replay = hash('sha256', $key['provider'] . '\0account\0' . ($event['id'] ?? 'revision-' . $event['revision']));
            if (isset($state['replays'][$replay])) return;
            if ($event['revision'] <= ($previous['revision'] ?? 0)) return;
            $suspended = $event['status'] === 'suspended';
            $mapping = $suspended ? ['admitted' => false, 'groups' => []]
                : ExternalGroupMapping::resolve($provider, $identity->groups, $documents['roles'], $documents['groups']);
            $state['sequence']++;
            $state['replays'][$replay] = ['expires_at' => time() + 600];
            $state['identities'][$identityKey] = [
                'revision' => $event['revision'], 'suspended' => $suspended, 'admitted' => $mapping['admitted'],
                'changed_sequence' => $state['sequence'],
            ];
            foreach ($documents['users'] as &$user) {
                if (($user['external_identity'] ?? null) !== $key) continue;
                // A verified full snapshot updates only provider-managed fields, never local disabled/home policy.
                $user['role'] = $provider['base_role'];
                $user['groups'] = $mapping['groups'];
                $user['auth_version'] = max(1, (int)($user['auth_version'] ?? 1)) + 1;
            }
            unset($user);
            // Credential removal is permanent across later account reactivation/deletion.
            $documents['webdav_credentials'] = array_values(array_filter($documents['webdav_credentials'],
                static fn(array $entry): bool => ($entry['external_identity'] ?? null) !== $key));
            foreach ($state['sessions'] as &$session) if ($session['identity_key'] === $identityKey) $session['revoked'] = true;
        });
    }

    public function sessionsRevoked(string $provider, string $issuer, ?string $subject, ?string $sid, string $eventId, int $expiresAt): void
    {
        if ($subject === null && $sid === null) throw new RuntimeException('A logout target is required.');
        ExternalState::transaction(static function (array &$documents) use ($provider, $issuer, $subject, $sid, $eventId, $expiresAt): void {
            $state = &$documents['external_auth'];
            $replay = hash('sha256', $provider . '\0logout\0' . $eventId);
            if (isset($state['replays'][$replay])) return;
            $state['sequence']++;
            $state['replays'][$replay] = ['expires_at' => max(time() + 600, $expiresAt)];
            if ($sid !== null) {
                $key = ExternalState::sidKey($provider, $issuer, $sid);
                $state['sid_revocations'][$key] = ['sequence' => $state['sequence'], 'expires_at' => time() + 86760];
            } elseif ($subject !== null) {
                $key = ExternalState::identityKey(['provider' => $provider, 'issuer' => $issuer, 'subject' => $subject]);
                $state['identities'][$key]['changed_sequence'] = $state['sequence'];
            }
            foreach ($state['sessions'] as &$session) {
                $identity = $session['identity'];
                if ($identity['provider'] === $provider && $identity['issuer'] === $issuer
                    && ($subject === null || $identity['subject'] === $subject)
                    && ($sid === null || $session['sid'] === $sid)) $session['revoked'] = true;
            }
        });
    }
}
