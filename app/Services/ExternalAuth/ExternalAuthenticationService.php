<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use App\Models\UserModel;
use App\Services\AuthenticationService;
use App\Services\SessionAccount;
use App\Services\StepUpAuthenticationService;
use CodeIgniter\HTTP\IncomingRequest;
use RuntimeException;

/** Owns one-time, session-bound login/reauthentication transactions. */
final class ExternalAuthenticationService
{
    public function __construct(private ?ExternalAuthConfiguration $configuration = null)
    {
        $this->configuration ??= new ExternalAuthConfiguration();
    }

    public function create(string $provider, string $returnTo, bool $reauthentication = false): array
    {
        $this->configuration->provider($provider);
        if ($reauthentication && (!session('isLoggedIn') || !SessionAccount::isExternal()
            || (session('external_auth')['provider'] ?? null) !== $provider
            || !$this->configuration->createProvider($provider)->supportsReauthentication())) {
            throw new RuntimeException('Provider reauthentication is unavailable.');
        }
        $state = bin2hex(random_bytes(32));
        $secret = bin2hex(random_bytes(32));
        $transaction = [
            'provider' => $provider, 'fingerprint' => $this->configuration->fingerprint($provider),
            'lifecycle_cursor' => ExternalState::snapshot()['sequence'],
            'state' => $state, 'nonce' => bin2hex(random_bytes(32)), 'verifier' => bin2hex(random_bytes(32)),
            'started_at' => time(), 'expires_at' => time() + 300, 'return' => $returnTo,
            'reauthentication' => $reauthentication, 'username' => session('username'), 'auth_version' => session('auth_version'),
            'exchange_hash' => hash('sha256', $secret), 'started' => false, 'consumed' => false, 'complete' => false,
        ];
        $transactions = $this->transactions();
        if (count($transactions) >= 4) throw new RuntimeException('Too many pending authentication transactions.');
        $transactions[$state] = $transaction;
        session()->set('external_auth_transactions', $transactions);
        return ['state' => $state, 'exchange_secret' => $secret];
    }

    public function begin(string $provider, string $state, IncomingRequest $request): ExternalIdentity|string
    {
        $transaction = $this->transaction($provider, $state);
        if ($transaction['started']) throw new RuntimeException('Authentication transaction was already started.');
        $this->store($state, array_replace($transaction, ['started' => true]));
        $result = $this->configuration->createProvider($provider)->begin($request, $this->context($transaction));
        if (is_string($result)) RestrictedHttpClient::origin($result);
        return $result;
    }

    public function complete(string $provider, string $state, IncomingRequest $request, ?ExternalIdentity $identity = null): array
    {
        $transaction = $this->transaction($provider, $state);
        if (!$transaction['started'] || $transaction['consumed']) throw new RuntimeException('Authentication transaction is not pending.');
        // Consume before network/proof validation, including failure paths.
        $this->store($state, array_replace($transaction, ['consumed' => true]));
        $identity ??= $this->configuration->createProvider($provider)->complete($request, $this->context($transaction));
        if ($transaction['reauthentication']) {
            $this->assertCurrentAccount($transaction);
            if ($identity->key($provider) !== (session('external_auth')['identity'] ?? null)
                || $identity->authenticatedAt === null || $identity->authenticatedAt < $transaction['started_at'] - 5
                || $identity->authenticatedAt > time() + 30) throw new RuntimeException('Fresh authentication for the same identity is required.');
        } elseif (session('isLoggedIn')) {
            throw new RuntimeException('An account is already signed in.');
        }
        $settings = $this->configuration->provider($provider);
        $user = (new ExternalAccountService())->synchronize($provider, $settings, $identity, $transaction['lifecycle_cursor']);
        if ($transaction['reauthentication']) {
            // A rights change invalidates the old session instead of upgrading it in place.
            if ($user['auth_version'] !== (int)$transaction['auth_version']) throw new RuntimeException('Account authorization changed. Sign in again.');
            (new ExternalSessionRegistry())->refreshContext($user, $identity->session, $transaction['lifecycle_cursor']);
            $this->store($state, array_replace($transaction, ['consumed' => true, 'complete' => true]));
        } else {
            if (!(new AuthenticationService())->startExternalSession($user, $provider, $transaction['fingerprint'], $settings['session_ttl_seconds'], $identity->session, $transaction['lifecycle_cursor'])) {
                throw new RuntimeException('External account session could not be started.');
            }
        }
        return ['reauthentication' => $transaction['reauthentication'], 'return' => $transaction['return']];
    }

    public function exchange(string $state, string $secret): ?string
    {
        $transactions = $this->transactions();
        $transaction = $transactions[$state] ?? null;
        if (!is_array($transaction) || !$transaction['reauthentication']
            || !hash_equals($transaction['exchange_hash'], hash('sha256', $secret))) throw new RuntimeException('Invalid reauthentication exchange.');
        $this->assertCurrentAccount($transaction);
        if (!$transaction['complete']) return null;
        unset($transactions[$state]);
        session()->set('external_auth_transactions', $transactions);
        return (new StepUpAuthenticationService())->issueExternalGrant();
    }

    public function cancel(string $state): void
    {
        $transactions = $this->transactions();
        unset($transactions[$state]);
        session()->set('external_auth_transactions', $transactions);
    }

    private function assertCurrentAccount(array $transaction): void
    {
        $user = (new UserModel())->getUser((string)session('username'));
        if (!session('isLoggedIn') || !$user || !empty($user['disabled']) || !ExternalSessionPolicy::isValid($user, $this->configuration)
            || $transaction['username'] !== session('username') || $transaction['auth_version'] !== session('auth_version')
            || (int)$user['auth_version'] !== (int)$transaction['auth_version']
            || !hash_equals($transaction['fingerprint'], $this->configuration->fingerprint($transaction['provider']))) {
            throw new RuntimeException('Reauthentication session is no longer current.');
        }
    }

    private function transaction(string $provider, string $state): array
    {
        $transaction = $this->transactions()[$state] ?? null;
        if (!is_array($transaction) || $transaction['provider'] !== $provider
            || !hash_equals($transaction['fingerprint'], $this->configuration->fingerprint($provider))) {
            throw new RuntimeException('Authentication transaction is invalid or expired.');
        }
        return $transaction;
    }

    private function transactions(): array
    {
        $transactions = session('external_auth_transactions');
        return is_array($transactions) ? array_filter($transactions, static fn($transaction): bool => is_array($transaction) && ($transaction['expires_at'] ?? 0) > time()) : [];
    }

    private function store(string $state, array $transaction): void
    {
        $transactions = $this->transactions();
        $transactions[$state] = $transaction;
        session()->set('external_auth_transactions', $transactions);
    }

    private function context(array $transaction): AuthenticationContext
    {
        return new AuthenticationContext(site_url('login/external/' . $transaction['provider'] . '/callback'),
            $transaction['state'], $transaction['nonce'], $transaction['verifier'], $transaction['started_at'], $transaction['reauthentication']);
    }
}
