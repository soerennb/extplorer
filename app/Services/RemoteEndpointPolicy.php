<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Single authorization boundary for every outbound FTP/SFTP connection.
 *
 * Allowlist entries are exact endpoint URIs, one per line, for example:
 *   sftp://files.example.com:22
 *   ftps://ftp.example.com:990
 */
final class RemoteEndpointPolicy
{
    /** @var callable|null */
    private $resolver;

    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver;
    }

    public function assertDirectLoginEnabled(): void
    {
        if (!$this->directLoginEnabled()) {
            throw new RuntimeException('Direct remote login is disabled by administrator policy.');
        }
    }

    public function directLoginEnabled(): bool
    {
        $configured = getenv('EXTPLORER_REMOTE_LOGIN_ENABLED');
        if ($configured === false || trim($configured) === '') {
            $configured = (new SettingsService())->get('remote_login_enabled', false);
        }

        return $this->toBoolean($configured);
    }

    /**
     * @return array{protocol: string, host: string, connect_host: string, port: int}
     */
    public function authorize(
        string $protocol,
        string $host,
        int $port,
        string $fingerprint = '',
        bool $tlsVerified = false
    ): array
    {
        $requestedProtocol = strtolower(trim($protocol));
        $requestedHost = $this->normalizeHost($host);

        try {
            return $this->authorizeEndpoint($requestedProtocol, $requestedHost, $port, $fingerprint, $tlsVerified);
        } catch (\Throwable $exception) {
            LogService::log(
                'Remote Endpoint Denied',
                '',
                sprintf('Protocol: %s; port: %d; reason: %s', $requestedProtocol, $port, $exception->getMessage())
            );
            throw $exception;
        }
    }

    /** @return array{protocol: string, host: string, connect_host: string, port: int} */
    private function authorizeEndpoint(string $protocol, string $host, int $port, string $fingerprint, bool $tlsVerified): array
    {
        if ($protocol === 'ssh2') {
            $protocol = 'sftp';
        }

        if (!in_array($protocol, ['ftp', 'ftps', 'sftp'], true)) {
            throw new InvalidArgumentException('Remote protocol is not supported.');
        }
        if ($host === '' || $port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Remote endpoint is invalid.');
        }

        (new RemoteSecurityPolicy())->assertProtocolAllowed($protocol, [
            'host_key_fingerprint' => $fingerprint,
            'tls_verified' => $tlsVerified,
        ]);

        if (!$this->endpointIsAllowlisted($protocol, $host, $port)) {
            throw new RuntimeException('Remote endpoint is not allowlisted.');
        }

        $ips = $this->resolveHostIps($host);
        if ($ips === []) {
            throw new RuntimeException('Remote endpoint could not be resolved.');
        }

        $allowPrivate = $this->privateTargetsEnabled();
        foreach ($ips as $ip) {
            if ($this->isReservedOrNonRoutable($ip)) {
                throw new RuntimeException('Remote endpoint resolves to a reserved target.');
            }
            if (!$allowPrivate && $this->isPrivate($ip)) {
                throw new RuntimeException('Remote endpoint resolves to a private target.');
            }
        }

        return [
            'protocol' => $protocol,
            'host' => $host,
            'connect_host' => $ips[0],
            'port' => $port,
        ];
    }

    /**
     * @return list<array{protocol: string, host: string, port: int}>
     */
    public function allowlistedEndpoints(): array
    {
        $configured = getenv('EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST');
        if ($configured !== false && trim($configured) !== '') {
            $entries = preg_split('/\r\n|\r|\n/', $configured) ?: [];
        } else {
            $entries = (new SettingsService())->get('remote_endpoint_allowlist', []);
        }

        if (!is_array($entries)) {
            return [];
        }

        $normalized = [];
        foreach ($entries as $entry) {
            if (is_array($entry)) {
                $protocol = strtolower(trim((string)($entry['protocol'] ?? '')));
                $host = (string)($entry['host'] ?? '');
                $port = (int)($entry['port'] ?? 0);
                $parsed = $this->endpoint($protocol, $host, $port);
            } else {
                $parsed = $this->parseEndpoint((string)$entry);
            }

            if ($parsed !== null) {
                $normalized[] = $parsed;
            }
        }

        $unique = [];
        foreach ($normalized as $endpoint) {
            $key = json_encode($endpoint, JSON_THROW_ON_ERROR);
            $unique[$key] = $endpoint;
        }

        return array_values($unique);
    }

    /** @return array{protocol: string, host: string, port: int}|null */
    public function parseEndpoint(string $value): ?array
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, "\0")) {
            return null;
        }

        $parsed = parse_url($value);
        if (!is_array($parsed) || !isset($parsed['scheme'], $parsed['host'], $parsed['port'])) {
            return null;
        }
        if (isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['path']) || isset($parsed['query']) || isset($parsed['fragment'])) {
            return null;
        }

        return $this->endpoint(
            (string)$parsed['scheme'],
            (string)$parsed['host'],
            (int)$parsed['port']
        );
    }

    /** @return array{protocol: string, host: string, port: int}|null */
    private function endpoint(string $protocol, string $host, int $port): ?array
    {
        $protocol = strtolower(trim($protocol));
        if ($protocol === 'ssh2') {
            $protocol = 'sftp';
        }
        $host = $this->normalizeHost($host);
        if (!in_array($protocol, ['ftp', 'ftps', 'sftp'], true)
            || $host === ''
            || $port < 1
            || $port > 65535
            || !$this->isValidHost($host)) {
            return null;
        }

        return ['protocol' => $protocol, 'host' => $host, 'port' => $port];
    }

    private function endpointIsAllowlisted(string $protocol, string $host, int $port): bool
    {
        foreach ($this->allowlistedEndpoints() as $endpoint) {
            if ($endpoint['protocol'] === $protocol
                && $endpoint['host'] === $host
                && $endpoint['port'] === $port) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHost(string $host): string
    {
        $host = trim(strtolower($host));
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }
        return $this->normalizeIp($host) ?? $host;
    }

    private function isValidHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        return preg_match('/\A(?=.{1,253}\z)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}\z/i', $host) === 1;
    }

    /** @return list<string> */
    private function resolveHostIps(string $host): array
    {
        if ($ip = $this->normalizeIp($host)) {
            return [$ip];
        }

        if ($this->resolver !== null) {
            $resolved = ($this->resolver)($host);
            if (!is_array($resolved)) {
                return [];
            }

            $ips = [];
            foreach ($resolved as $candidate) {
                if (!is_string($candidate)) {
                    continue;
                }
                $normalized = $this->normalizeIp($candidate);
                if ($normalized !== null) {
                    $ips[] = $normalized;
                }
            }
            return array_values(array_unique($ips));
        }

        $ips = [];
        $records = dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                foreach (['ip', 'ipv6'] as $key) {
                    if (isset($record[$key]) && is_string($record[$key])) {
                        $normalized = $this->normalizeIp($record[$key]);
                        if ($normalized !== null) {
                            $ips[] = $normalized;
                        }
                    }
                }
            }
        }

        if ($ips === []) {
            foreach (gethostbynamel($host) ?: [] as $ip) {
                $normalized = $this->normalizeIp((string)$ip);
                if ($normalized !== null) {
                    $ips[] = $normalized;
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function normalizeIp(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        // Treat IPv4-mapped IPv6 addresses as IPv4 before applying private
        // and reserved-range checks. PHP's filter flags otherwise classify
        // mapped addresses inconsistently across versions.
        if (strlen($packed) === 16
            && substr($packed, 0, 10) === str_repeat("\0", 10)
            && substr($packed, 10, 2) === "\xff\xff") {
            $packed = substr($packed, 12);
        }

        $normalized = inet_ntop($packed);
        return $normalized === false ? null : strtolower($normalized);
    }

    private function privateTargetsEnabled(): bool
    {
        $value = getenv('EXTPLORER_REMOTE_ALLOW_PRIVATE_TARGETS');
        return $this->toBoolean($value === false ? false : $value);
    }

    private function isPrivate(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    private function isReservedOrNonRoutable(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $ip === '::' || $ip === '::1'
                || $this->ipv6MatchesPrefix($ip, 'fe80::', 10)
                || $this->ipv6MatchesPrefix($ip, 'ff00::', 8)
                || $this->ipv6MatchesPrefix($ip, '2001:db8::', 32);
        }

        return false;
    }

    private function ipv6MatchesPrefix(string $ip, string $network, int $prefixLength): bool
    {
        $ipPacked = inet_pton($ip);
        $networkPacked = inet_pton($network);
        if ($ipPacked === false || $networkPacked === false) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;
        if ($fullBytes > 0 && substr($ipPacked, 0, $fullBytes) !== substr($networkPacked, 0, $fullBytes)) {
            return false;
        }
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($ipPacked[$fullBytes]) & $mask) === (ord($networkPacked[$fullBytes]) & $mask);
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
