<?php

declare(strict_types=1);

namespace ExtplorerAuth;

use App\Services\ExternalAuth\AuthenticationContext;
use App\Services\ExternalAuth\ExternalAuthProviderInterface;
use App\Services\ExternalAuth\ExternalIdentity;
use App\Services\ExternalAuth\OperatorFile;
use App\Services\ExternalAuth\RestrictedHttpClient;
use App\Services\SecretReader;
use CodeIgniter\HTTP\IncomingRequest;
use RuntimeException;

/** Example bridge: the website signs a short-lived, transaction-bound identity assertion. */
final class WebsiteProvider implements ExternalAuthProviderInterface, \App\Services\ExternalAuth\ExternalLogoutProviderInterface
{
    public function __construct(private array $configuration)
    {
        RestrictedHttpClient::origin($configuration['login_url']);
        if (strlen(SecretReader::file(OperatorFile::path($configuration['signing_key_file']))) < 32) {
            throw new RuntimeException('Website signing key must contain at least 32 bytes.');
        }
    }

    public function supportsReauthentication(): bool
    {
        return ($this->configuration['supports_reauthentication'] ?? false) === true;
    }

    public function begin(IncomingRequest $request, AuthenticationContext $context): string
    {
        return $this->configuration['login_url'] . '?' . http_build_query([
            'callback' => $context->callbackUrl, 'state' => $context->state, 'nonce' => $context->nonce,
            'reauthenticate' => $context->reauthentication ? '1' : '0',
        ]);
    }

    public function logoutRequest(string $returnUrl, string $state, ?\App\Services\ExternalAuth\ProviderSession $session): \App\Services\ExternalAuth\LogoutRequest
    {
        if (empty($this->configuration['rp_logout_enabled'])) throw new RuntimeException('Website logout is disabled.');
        $time = (string)time();
        $key = SecretReader::file(OperatorFile::path($this->configuration['signing_key_file']));
        return new \App\Services\ExternalAuth\LogoutRequest($this->configuration['logout_url'], [
            'callback' => $returnUrl, 'state' => $state, 'time' => $time,
            'signature' => hash_hmac('sha256', "logout\n" . $time . "\n" . $returnUrl . "\n" . $state, $key),
        ]);
    }

    public function complete(IncomingRequest $request, AuthenticationContext $context): ExternalIdentity
    {
        $ticket = $request->getGet('ticket');
        if (!is_string($ticket) || strlen($ticket) > 16384 || substr_count($ticket, '.') !== 1) throw new RuntimeException('Website ticket is missing.');
        [$payload, $signature] = explode('.', $ticket, 2);
        $key = SecretReader::file(OperatorFile::path($this->configuration['signing_key_file']));
        if (!hash_equals(hash_hmac('sha256', $payload, $key), $signature)) throw new RuntimeException('Website ticket signature is invalid.');
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        $claims = $decoded === false ? null : json_decode($decoded, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($claims) || ($claims['iss'] ?? null) !== $this->configuration['issuer']
            || ($claims['aud'] ?? null) !== $context->callbackUrl || ($claims['state'] ?? null) !== $context->state
            || ($claims['nonce'] ?? null) !== $context->nonce || !is_int($claims['iat'] ?? null) || !is_int($claims['exp'] ?? null)
            || $claims['iat'] < $context->startedAt - 5 || $claims['iat'] > time() + 30
            || $claims['exp'] <= time() || $claims['exp'] > $claims['iat'] + 60
            || !is_string($claims['sub'] ?? null) || !is_string($claims['name'] ?? null)
            || !is_array($claims['groups'] ?? null) || !is_int($claims['auth_time'] ?? null)) {
            throw new RuntimeException('Website ticket claims are invalid.');
        }
        return new ExternalIdentity($claims['iss'], $claims['sub'], $claims['name'], $claims['groups'], $claims['auth_time'],
            new \App\Services\ExternalAuth\ProviderSession($claims['sid'] ?? null));
    }
}
