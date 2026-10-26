<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use App\Services\RemoteCredentialService;
use RuntimeException;

/** Short-lived, browser-bound anonymous transactions survive destruction of the login session. */
final class ExternalLogoutService
{
    public const COOKIE_NAME = 'extplorer_logout';

    public static function available(): bool
    {
        if (!\App\Services\SessionAccount::isExternal()) return false;
        try {
            $configuration = new ExternalAuthConfiguration();
            $provider = (string)session('external_auth')['provider'];
            return !empty($configuration->provider($provider)['rp_logout_enabled'])
                && $configuration->createProvider($provider) instanceof ExternalLogoutProviderInterface;
        } catch (\Throwable) { return false; }
    }

    public function prepare(): array
    {
        $external = session('external_auth');
        if (!is_array($external) || !self::available()) throw new RuntimeException('Provider logout is unavailable.');
        $configuration = new ExternalAuthConfiguration();
        $provider = $external['provider'];
        $instance = $configuration->createProvider($provider);
        if (!$instance instanceof ExternalLogoutProviderInterface) throw new RuntimeException('Provider logout is unavailable.');
        $state = bin2hex(random_bytes(32));
        $cookie = bin2hex(random_bytes(32));
        // Only verified, encrypted context from this registered session is used as a hint.
        $record = ExternalState::snapshot()['sessions'][$external['registration'] ?? ''] ?? null;
        if (!is_array($record) || !empty($record['revoked']) || $record['expires_at'] <= time()
            || $record['identity'] !== $external['identity']) throw new RuntimeException('External logout session is no longer current.');
        $hint = $record['id_token_hint'] === '' ? null : (new RemoteCredentialService())->reveal($record['id_token_hint']);
        $context = new ProviderSession($record['sid'], $hint);
        ExternalState::transaction(static function (array &$documents) use ($state, $cookie, $provider): void {
            $documents['external_auth']['logouts'][hash('sha256', $state)] = [
                'cookie_hash' => hash('sha256', $cookie), 'provider' => $provider, 'expires_at' => time() + 300,
            ];
        });
        return ['provider' => $instance, 'context' => $context, 'state' => $state, 'cookie' => $cookie];
    }

    public function complete(string $state, string $cookie): bool
    {
        if (preg_match('/\A[a-f0-9]{64}\z/', $state) !== 1 || preg_match('/\A[a-f0-9]{64}\z/', $cookie) !== 1) return false;
        return ExternalState::transaction(static function (array &$documents) use ($state, $cookie): bool {
            $key = hash('sha256', $state);
            $transaction = $documents['external_auth']['logouts'][$key] ?? null;
            if (!is_array($transaction) || $transaction['expires_at'] <= time()
                || !hash_equals($transaction['cookie_hash'], hash('sha256', $cookie))) return false;
            unset($documents['external_auth']['logouts'][$key]);
            return true;
        });
    }
}
