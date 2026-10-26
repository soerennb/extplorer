<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use App\Services\SecretReader;
use CodeIgniter\HTTP\IncomingRequest;
use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\Issuer;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadata;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Session\AuthSession;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use RuntimeException;

/** Code flow only; protocol verification is delegated to the OIDC library. */
final class OidcProvider implements ExternalAuthProviderInterface, ExternalLogoutProviderInterface
{
    private HttpClientInterface $http;
    private RestrictedHttpClient $policy;

    public function __construct(private array $configuration, ?HttpClientInterface $http = null)
    {
        $this->policy = new RestrictedHttpClient($configuration);
        $this->http = $http ?? $this->policy;
        // Validate secrets at readiness, without contacting the provider.
        SecretReader::file(OperatorFile::path($configuration['client_secret_file']));
    }

    public function supportsReauthentication(): bool { return true; }

    public function begin(IncomingRequest $request, AuthenticationContext $context): string
    {
        $parameters = [
            'response_type' => 'code', 'response_mode' => 'query', 'scope' => 'openid profile',
            'state' => $context->state, 'nonce' => $context->nonce,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $context->codeVerifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ];
        if ($context->reauthentication) $parameters += ['prompt' => 'login', 'max_age' => '0'];
        if (isset($this->configuration['acr_values'])) $parameters['acr_values'] = $this->configuration['acr_values'];
        return $this->authorization()->getAuthorizationUri($this->client($context), $parameters);
    }

    public function complete(IncomingRequest $request, AuthenticationContext $context): ExternalIdentity
    {
        // Accept exactly the code-flow response. Never accept tokens from the browser.
        $code = $request->getGet('code');
        if (!is_string($code) || $code === '' || strlen($code) > 4096 || $request->getGet('error') !== null
            || $request->getGet('id_token') !== null || $request->getGet('access_token') !== null) {
            throw new RuntimeException('OIDC authorization failed.');
        }
        $responseIssuer = $request->getGet('iss');
        if ($responseIssuer !== null && $responseIssuer !== $this->configuration['issuer']) throw new RuntimeException('OIDC response issuer mismatch.');
        $authSession = AuthSession::fromArray(['state' => $context->state, 'nonce' => $context->nonce, 'code_verifier' => $context->codeVerifier]);
        $tokens = $this->authorization()->callback($this->client($context), ['code' => $code], $context->callbackUrl, $authSession);
        if ($tokens->getIdToken() === null) throw new RuntimeException('OIDC ID token is required.');
        $claims = $tokens->claims();
        if (!is_string($claims['sub'] ?? null) || !is_string($claims['iss'] ?? null)) throw new RuntimeException('OIDC identity claims are missing.');
        if (isset($this->configuration['acr_values']) && !in_array($claims['acr'] ?? null, explode(' ', $this->configuration['acr_values']), true)) {
            throw new RuntimeException('OIDC authentication assurance is insufficient.');
        }
        $authenticatedAt = $claims['auth_time'] ?? null;
        if ($authenticatedAt !== null && !is_int($authenticatedAt)) throw new RuntimeException('OIDC authentication time is invalid.');
        $groups = [];
        foreach ($this->configuration['group_claims'] ?? ['groups'] as $path) {
            $value = $claims;
            foreach (explode('.', $path) as $part) $value = is_array($value) ? ($value[$part] ?? null) : null;
            if ($value === null) continue;
            if (!is_array($value) || !array_is_list($value) || array_filter($value, 'is_string') !== $value) {
                throw new RuntimeException('OIDC group claim is invalid.');
            }
            $groups = array_merge($groups, $value);
        }
        $displayName = $claims['name'] ?? $claims['preferred_username'] ?? $claims['sub'];
        if (!is_string($displayName)) throw new RuntimeException('OIDC display name is invalid.');
        $sid = $claims['sid'] ?? null;
        if ($sid !== null && !is_string($sid)) throw new RuntimeException('OIDC session claim is invalid.');
        $hint = !empty($this->configuration['rp_logout_enabled']) ? $tokens->getIdToken() : null;
        return new ExternalIdentity($claims['iss'], $claims['sub'], $displayName, array_values(array_unique($groups)), $authenticatedAt,
            new ProviderSession($sid, $hint));
    }

    public function logoutRequest(string $returnUrl, string $state, ?ProviderSession $session): LogoutRequest
    {
        if (empty($this->configuration['rp_logout_enabled'])) throw new RuntimeException('Provider logout is disabled.');
        $metadata = $this->metadata();
        $endpoint = $metadata['end_session_endpoint'] ?? null;
        if (!is_string($endpoint)) throw new RuntimeException('OIDC logout endpoint is missing.');
        $this->policy->assertAllowedUrl($endpoint);
        $parameters = ['client_id' => $this->configuration['client_id'], 'post_logout_redirect_uri' => $returnUrl, 'state' => $state];
        if ($session?->idTokenHint !== null) $parameters['id_token_hint'] = $session->idTokenHint;
        return new LogoutRequest($endpoint, $parameters);
    }

    /** Verify the logout-specific claims separately from ID-token authentication. */
    public function verifyLogoutToken(string $token): array
    {
        if (empty($this->configuration['backchannel_logout_enabled']) || strlen($token) > 32768) throw new RuntimeException('OIDC backchannel logout is disabled.');
        $jws = (new \Jose\Component\Signature\Serializer\CompactSerializer())->unserialize($token);
        $header = $jws->getSignature(0)->getProtectedHeader();
        if (($header['alg'] ?? null) !== 'RS256' || isset($header['jku']) || isset($header['x5u']) || isset($header['crit'])
            || (isset($header['typ']) && !in_array($header['typ'], ['JWT', 'logout+jwt'], true))) throw new RuntimeException('Invalid logout token header.');
        $metadata = $this->metadata();
        $response = $this->http->sendRequest(new Request('GET', $metadata['jwks_uri']));
        if ($response->getStatusCode() !== 200) throw new RuntimeException('OIDC logout keys are unavailable.');
        $keys = \Jose\Component\Core\JWKSet::createFromJson((string)$response->getBody());
        $verifier = new \Jose\Component\Signature\JWSVerifier(new \Jose\Component\Core\AlgorithmManager([new \Jose\Component\Signature\Algorithm\RS256()]));
        if (!$verifier->verifyWithKeySet($jws, $keys, 0)) throw new RuntimeException('Invalid logout token signature.');
        $payload = (string)$jws->getPayload();
        $claims = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
        $objects = json_decode($payload, false, 16, JSON_THROW_ON_ERROR);
        $now = time();
        if (!is_array($claims) || ($claims['iss'] ?? null) !== $this->configuration['issuer']
            || (!is_string($claims['aud'] ?? null) && !is_array($claims['aud'] ?? null))
            || (is_array($claims['aud']) && (!array_is_list($claims['aud']) || array_filter($claims['aud'], 'is_string') !== $claims['aud']))
            || !in_array($this->configuration['client_id'], is_array($claims['aud']) ? $claims['aud'] : [$claims['aud']], true)
            || !is_int($claims['iat'] ?? null) || $claims['iat'] > $now + 30 || $claims['iat'] < $now - 300
            || !is_int($claims['exp'] ?? null) || $claims['exp'] <= $now || $claims['exp'] <= $claims['iat'] || $claims['exp'] > $claims['iat'] + 3600
            || !is_string($claims['jti'] ?? null) || $claims['jti'] === '' || strlen($claims['jti']) > 255
            || array_key_exists('nonce', $claims) || !is_array($claims['events'] ?? null)
            || !array_key_exists('http://schemas.openid.net/event/backchannel-logout', $claims['events'])
            || !is_object($objects->events->{'http://schemas.openid.net/event/backchannel-logout'} ?? null)) {
            throw new RuntimeException('Invalid logout token claims.');
        }
        foreach (['sub', 'sid'] as $field) {
            if (isset($claims[$field]) && (!is_string($claims[$field]) || $claims[$field] === '' || strlen($claims[$field]) > 255)) {
                throw new RuntimeException('Invalid logout token target.');
            }
        }
        if (!isset($claims['sub']) && !isset($claims['sid'])) throw new RuntimeException('Logout target is missing.');
        return $claims;
    }

    private function authorization(): AuthorizationService
    {
        $builder = new AuthorizationServiceBuilder();
        $builder->setHttpClient($this->http);
        $builder->setRequestFactory(new Psr17Factory());
        return $builder->build();
    }

    private function client(AuthenticationContext $context): ClientInterface
    {
        $metadata = $this->metadata();
        $keys = (new JwksProviderBuilder())->withHttpClient($this->http)->withRequestFactory(new Psr17Factory())
            ->withJwksUri($metadata['jwks_uri'])->build();
        $clientMetadata = ClientMetadata::fromArray([
            'client_id' => $this->configuration['client_id'],
            'client_secret' => SecretReader::file(OperatorFile::path($this->configuration['client_secret_file'])),
            'token_endpoint_auth_method' => 'client_secret_basic', 'response_types' => ['code'],
            'grant_types' => ['authorization_code'], 'redirect_uris' => [$context->callbackUrl],
            'id_token_signed_response_alg' => 'RS256',
        ]);
        return (new ClientBuilder())->setIssuer(new Issuer(IssuerMetadata::fromArray($metadata), $keys))
            ->setClientMetadata($clientMetadata)->setHttpClient($this->http)->build();
    }

    private function metadata(): array
    {
        $issuer = $this->configuration['issuer'];
        $response = $this->http->sendRequest(new Request('GET', rtrim($issuer, '/') . '/.well-known/openid-configuration'));
        if ($response->getStatusCode() !== 200) throw new RuntimeException('OIDC discovery failed.');
        $metadata = json_decode((string)$response->getBody(), true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($metadata) || ($metadata['issuer'] ?? null) !== $issuer) throw new RuntimeException('OIDC discovery issuer mismatch.');
        foreach (['authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $endpoint) {
            if (!is_string($metadata[$endpoint] ?? null)) throw new RuntimeException('OIDC discovery endpoint is missing.');
            $this->policy->assertAllowedUrl($metadata[$endpoint]);
        }
        if (!in_array('S256', $metadata['code_challenge_methods_supported'] ?? [], true)) throw new RuntimeException('OIDC provider must support PKCE S256.');
        return $metadata;
    }
}
