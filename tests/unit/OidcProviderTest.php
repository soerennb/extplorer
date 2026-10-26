<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ExternalAuth\AuthenticationContext;
use App\Services\ExternalAuth\OidcProvider;
use App\Services\ExternalAuth\RestrictedHttpClient;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OidcProviderTest extends CIUnitTestCase
{
    private string $secretFile;
    private array $configuration;
    private array $claims;
    private AuthenticationContext $context;
    private \OpenSSLAsymmetricKey $privateKey;
    private array $publicKey;
    private array $requests = [];
    private bool $badSignature = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->secretFile = tempnam(sys_get_temp_dir(), 'extplorer-oidc-secret-');
        file_put_contents($this->secretFile, 'test-client-secret');
        $this->configuration = ['issuer' => 'https://idp.test/realm', 'client_id' => 'extplorer',
            'client_secret_file' => $this->secretFile, 'group_claims' => ['groups', 'realm_access.roles']];
        $this->context = new AuthenticationContext('https://files.test/extplorer/index.php/login/external/company/callback',
            bin2hex(random_bytes(32)), bin2hex(random_bytes(32)), bin2hex(random_bytes(32)), time());
        $this->claims = ['iss' => 'https://idp.test/realm', 'sub' => 'opaque-user-id', 'aud' => 'extplorer',
            'exp' => time() + 300, 'iat' => time(), 'auth_time' => time(), 'nonce' => $this->context->nonce,
            'name' => 'Alice', 'groups' => ['members'], 'realm_access' => ['roles' => ['editors']]];
        $this->privateKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $details = openssl_pkey_get_details($this->privateKey);
        $this->publicKey = ['kty' => 'RSA', 'kid' => 'fixture-key', 'use' => 'sig', 'alg' => 'RS256',
            'n' => self::base64url($details['rsa']['n']), 'e' => self::base64url($details['rsa']['e'])];
    }

    protected function tearDown(): void
    {
        unlink($this->secretFile);
        parent::tearDown();
    }

    private static function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function provider(): OidcProvider
    {
        $transport = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $this->requests[] = [$method, $url, $options];
            $metadata = [
                'issuer' => 'https://idp.test/realm', 'authorization_endpoint' => 'https://idp.test/realm/authorize',
                'token_endpoint' => 'https://idp.test/realm/token', 'jwks_uri' => 'https://idp.test/realm/keys',
                'response_types_supported' => ['code'], 'subject_types_supported' => ['public'],
                'id_token_signing_alg_values_supported' => ['RS256'], 'code_challenge_methods_supported' => ['S256'],
            ];
            if (str_ends_with($url, '/.well-known/openid-configuration')) $body = $metadata;
            elseif (str_ends_with($url, '/keys')) $body = ['keys' => [$this->publicKey]];
            else {
                $encoded = self::base64url(json_encode(['alg' => 'RS256', 'kid' => 'fixture-key'])) . '.' . self::base64url(json_encode($this->claims));
                openssl_sign($encoded, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
                if ($this->badSignature) $signature[0] = chr(ord($signature[0]) ^ 1);
                $body = ['access_token' => 'fixture-access-token', 'token_type' => 'Bearer', 'id_token' => $encoded . '.' . self::base64url($signature)];
            }
            return new MockResponse(json_encode($body), ['response_headers' => ['content-type: application/json']]);
        });
        $http = new RestrictedHttpClient($this->configuration, $transport, static fn(): array => ['93.184.216.34']);
        return new OidcProvider($this->configuration, $http);
    }

    public function testCodeFlowUsesPkceNonceAndStrictlyValidatedTokens(): void
    {
        $provider = $this->provider();
        $url = $provider->begin(Services::request(), $this->context);
        parse_str(parse_url($url, PHP_URL_QUERY), $parameters);
        $this->assertSame('code', $parameters['response_type']);
        $this->assertSame('S256', $parameters['code_challenge_method']);
        $this->assertSame(self::base64url(hash('sha256', $this->context->codeVerifier, true)), $parameters['code_challenge']);
        $this->assertSame($this->context->nonce, $parameters['nonce']);
        $request = Services::request();
        $request->setGlobal('get', ['code' => 'one-time-code']);
        $identity = $provider->complete($request, $this->context);
        $this->assertSame('opaque-user-id', $identity->subject);
        $this->assertSame(['members', 'editors'], $identity->groups);
        $this->assertSame('Alice', $identity->displayName);
        $tokenRequests = array_values(array_filter($this->requests, static fn($entry): bool => $entry[0] === 'POST'));
        parse_str($tokenRequests[0][2]['body'], $body);
        $this->assertSame($this->context->codeVerifier, $body['code_verifier']);
        $this->assertSame($this->context->callbackUrl, $body['redirect_uri']);
        foreach ($this->requests as $entry) {
            $this->assertSame(0, $entry[2]['max_redirects']);
            $this->assertSame('93.184.216.34', $entry[2]['resolve']['idp.test']);
        }
    }

    public static function invalidClaims(): iterable
    {
        yield 'issuer' => ['iss', 'https://attacker.test'];
        yield 'audience' => ['aud', 'another-client'];
        yield 'nonce' => ['nonce', 'wrong-nonce'];
        yield 'missing nonce' => ['nonce', null];
        yield 'expired' => ['exp', 1];
        yield 'future issue time' => ['iat', 4000000000];
        yield 'missing subject' => ['sub', null];
        yield 'malformed groups' => ['groups', ['admin' => true]];
    }

    #[DataProvider('invalidClaims')]
    public function testRejectsInvalidClaims(string $claim, mixed $value): void
    {
        $this->claims[$claim] = $value;
        Services::request()->setGlobal('get', ['code' => 'one-time-code']);
        $this->expectException(\Throwable::class);
        $this->provider()->complete(Services::request(), $this->context);
    }

    public function testRejectsInvalidSignature(): void
    {
        $this->badSignature = true;
        Services::request()->setGlobal('get', ['code' => 'one-time-code']);
        $this->expectException(\Throwable::class);
        $this->provider()->complete(Services::request(), $this->context);
    }

    public function testReauthenticationRequestsFreshAuthentication(): void
    {
        $context = new AuthenticationContext($this->context->callbackUrl, $this->context->state,
            $this->context->nonce, $this->context->codeVerifier, time(), true);
        $url = $this->provider()->begin(Services::request(), $context);
        parse_str(parse_url($url, PHP_URL_QUERY), $parameters);
        $this->assertSame('0', $parameters['max_age']);
        $this->assertSame('login', $parameters['prompt']);
    }

    public function testHttpTransportRejectsUnlistedOriginsAndPrivateDnsBeforeSendingAnything(): void
    {
        $sent = 0;
        $transport = new MockHttpClient(function () use (&$sent): MockResponse { $sent++; return new MockResponse('{}'); });
        $http = new RestrictedHttpClient($this->configuration, $transport, static fn(): array => ['127.0.0.1']);
        foreach (['https://attacker.test/keys', 'https://idp.test/keys', 'http://idp.test/keys', 'https://user:pass@idp.test/keys'] as $url) {
            try { $http->sendRequest(new Request('GET', $url)); $this->fail('Unsafe endpoint must fail.'); }
            catch (\Psr\Http\Client\ClientExceptionInterface $exception) { $this->assertSame('OIDC HTTP request failed.', $exception->getMessage()); }
        }
        $this->assertSame(0, $sent);
    }

    public function testHttpTransportRejectsRedirectsAndOversizedResponses(): void
    {
        foreach ([new MockResponse('', ['http_code' => 302, 'response_headers' => ['location: https://attacker.test/']]),
            new MockResponse(str_repeat('a', 1048577))] as $response) {
            $http = new RestrictedHttpClient($this->configuration, new MockHttpClient($response), static fn(): array => ['93.184.216.34']);
            try { $http->sendRequest(new Request('GET', 'https://idp.test/keys')); $this->fail('Unsafe response must fail.'); }
            catch (\Psr\Http\Client\ClientExceptionInterface $exception) { $this->assertSame('OIDC HTTP request failed.', $exception->getMessage()); }
        }
    }
}
