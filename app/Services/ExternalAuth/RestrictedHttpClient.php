<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** All OIDC discovery, JWKS and credential-bearing requests cross this boundary. */
final class RestrictedHttpClient implements ClientInterface
{
    private array $origins;
    private bool $allowPrivate;
    /** @var callable|null */
    private $resolver;

    public function __construct(array $provider, private ?HttpClientInterface $transport = null, ?callable $resolver = null)
    {
        $this->origins = array_unique(array_merge([self::origin($provider['issuer'])], $provider['allowed_origins'] ?? []));
        $this->allowPrivate = ($provider['allow_private_targets'] ?? false) === true;
        $this->resolver = $resolver;
    }

    public static function origin(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || preg_match('/[\\\\\x00-\x20\x7f]/', $url)) {
            throw new RuntimeException('OIDC requires an absolute HTTPS URL without credentials.');
        }
        return 'https://' . strtolower($parts['host']) . (isset($parts['port']) && $parts['port'] !== 443 ? ':' . $parts['port'] : '');
    }

    public function assertAllowedUrl(string $url): void
    {
        if (!in_array(self::origin($url), $this->origins, true)) throw new RuntimeException('OIDC endpoint origin is not allowed.');
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $url = (string)$request->getUri();
            $this->assertAllowedUrl($url);
            if (!in_array($request->getMethod(), ['GET', 'POST'], true)) throw new RuntimeException('OIDC HTTP method is not supported.');
            $host = trim(strtolower($request->getUri()->getHost()), '[]');
            $addresses = $this->addresses($host);
            if ($addresses === []) throw new RuntimeException('OIDC endpoint DNS lookup failed.');
            foreach ($addresses as $address) {
                if (!filter_var($address, FILTER_VALIDATE_IP)
                    || (!$this->allowPrivate && !filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
                    throw new RuntimeException('OIDC endpoint address is not allowed.');
                }
            }
            $transport = $this->transport ?? HttpClient::create();
            $response = $transport->request($request->getMethod(), $url, [
                'headers' => $request->getHeaders(), 'body' => (string)$request->getBody(),
                'resolve' => [$host => $addresses[0]], 'max_redirects' => 0,
                'timeout' => 10, 'max_duration' => 15, 'verify_peer' => true, 'verify_host' => true,
                'proxy' => '', 'buffer' => false,
            ]);
            $body = '';
            try {
                foreach ($transport->stream($response) as $chunk) {
                    $body .= $chunk->getContent();
                    if (strlen($body) > 1048576) throw new RuntimeException('OIDC response exceeds its size limit.');
                }
                $status = $response->getStatusCode();
                if ($status >= 300 && $status < 400) throw new RuntimeException('OIDC endpoint redirects are not followed.');
                return new Response($status, $response->getHeaders(false), $body);
            } finally {
                $response->cancel();
            }
        } catch (\Throwable $exception) {
            // Never expose URLs, IdP response bodies or credentials in exception messages.
            throw new OidcHttpException('OIDC HTTP request failed.', 0, $exception);
        }
    }

    /** @return list<string> */
    private function addresses(string $host): array
    {
        if ($this->resolver !== null) return ($this->resolver)($host);
        if (filter_var($host, FILTER_VALIDATE_IP)) return [$host];
        $addresses = [];
        foreach (dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($ip)) $addresses[] = $ip;
        }
        return array_values(array_unique($addresses));
    }
}

final class OidcHttpException extends RuntimeException implements ClientExceptionInterface {}
