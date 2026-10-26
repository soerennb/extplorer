<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use App\Services\SecretReader;
use RuntimeException;

/** Separate HMAC key and domain binding prevent reusing login assertions as lifecycle events. */
final class SignedExternalEvents
{
    public function receive(string $providerId, string $body, string $timestamp, string $signature): void
    {
        $configuration = new ExternalAuthConfiguration();
        $provider = $configuration->provider($providerId);
        if (!isset($provider['events_secret_file']) || strlen($body) > 65536
            || preg_match('/\A[0-9]{10}\z/', $timestamp) !== 1 || abs(time() - (int)$timestamp) > 300
            || preg_match('/\Av1=[a-f0-9]{64}\z/', $signature) !== 1) throw new RuntimeException('External event is invalid.');
        $secret = SecretReader::file(OperatorFile::path($provider['events_secret_file']));
        $expected = 'v1=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        if (strlen($secret) < 32 || !hash_equals($expected, $signature)) throw new RuntimeException('External event signature is invalid.');
        $event = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
        $audience = site_url('auth/events/' . $providerId);
        if (!is_array($event) || ($event['iss'] ?? null) !== $provider['issuer'] || ($event['aud'] ?? null) !== $audience
            || ($event['provider'] ?? null) !== $providerId || !is_string($event['id'] ?? null) || $event['id'] === '' || strlen($event['id']) > 128
            || ($event['iat'] ?? null) !== (int)$timestamp) throw new RuntimeException('External event target is invalid.');
        $lifecycle = new ExternalLifecycleService();
        if (($event['type'] ?? '') === 'account.updated') {
            if (!is_int($event['revision'] ?? null) || $event['revision'] < 1 || !is_string($event['sub'] ?? null)
                || !in_array($event['status'] ?? null, ['active', 'suspended'], true) || !is_array($event['groups'] ?? null)) {
                throw new RuntimeException('External account event is invalid.');
            }
            $lifecycle->accountUpdated($providerId, $provider, $event);
            return;
        }
        if (($event['type'] ?? '') !== 'session.revoked') throw new RuntimeException('Unknown external event type.');
        $subject = $event['sub'] ?? null;
        $sid = $event['sid'] ?? null;
        if (($subject !== null && (!is_string($subject) || $subject === '' || strlen($subject) > 255))
            || ($sid !== null && (!is_string($sid) || $sid === '' || strlen($sid) > 255))) throw new RuntimeException('Invalid session target.');
        $lifecycle->sessionsRevoked($providerId, $provider['issuer'], $subject, $sid, $event['id'], (int)$timestamp + 600);
    }
}
