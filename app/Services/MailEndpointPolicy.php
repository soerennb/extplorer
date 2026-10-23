<?php

namespace App\Services;

/**
 * Destination policy for the SMTP host chosen in the web settings.
 *
 * Cloud metadata, link-local and other non-routable addresses are never
 * reachable. Loopback and private networks, such as a local relay, require
 * EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS from the operator. Every resolved
 * address must pass; the check runs right before each connection.
 */
final class MailEndpointPolicy
{
    /** @var list<string> */
    private const ALWAYS_DENIED = [
        '0.0.0.0/8', '169.254.0.0/16', '224.0.0.0/4', '240.0.0.0/4', '100.100.100.200/32',
        '::/128', 'fe80::/10', 'ff00::/8', 'fd00:ec2::254/128',
    ];

    /** @var list<string> */
    private const INTERNAL = [
        '127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '100.64.0.0/10',
        '::1/128', 'fc00::/7',
    ];

    /** @var \Closure(string): list<string>|null */
    private ?\Closure $resolver;

    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver === null ? null : \Closure::fromCallable($resolver);
    }

    public static function privateTargetsEnabled(): bool
    {
        return filter_var(getenv('EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS') ?: '0', FILTER_VALIDATE_BOOLEAN);
    }

    public function assertAllowed(string $host): void
    {
        $host = trim($host, '[] ');
        $addresses = $this->normalizeIp($host) !== null ? [$this->normalizeIp($host)] : $this->resolve($host);
        if ($addresses === []) {
            throw new MailConfigurationException('smtp_target_unresolved');
        }

        foreach ($addresses as $address) {
            $this->assertAddressAllowed($address);
        }
    }

    /** Save-time feedback for address literals; host names are checked when connecting. */
    public function assertLiteralAllowed(string $host): void
    {
        $address = $this->normalizeIp(trim($host, '[] '));
        if ($address !== null) {
            $this->assertAddressAllowed($address);
        }
    }

    private function assertAddressAllowed(string $address): void
    {
        if ($this->matchesAny($address, self::ALWAYS_DENIED)
            || ($this->matchesAny($address, self::INTERNAL) && !self::privateTargetsEnabled())) {
            throw new MailConfigurationException('smtp_target_denied');
        }
    }

    /** @return list<string> */
    private function resolve(string $host): array
    {
        if ($host === '') {
            return [];
        }

        $candidates = [];
        if ($this->resolver !== null) {
            $candidates = ($this->resolver)($host);
        } else {
            foreach (dns_get_record($host, DNS_A + DNS_AAAA) ?: [] as $record) {
                foreach (['ip', 'ipv6'] as $key) {
                    if (isset($record[$key])) $candidates[] = $record[$key];
                }
            }
            if ($candidates === []) {
                $candidates = gethostbynamel($host) ?: [];
            }
        }

        $addresses = [];
        foreach ($candidates as $candidate) {
            $address = is_string($candidate) ? $this->normalizeIp($candidate) : null;
            if ($address !== null) $addresses[] = $address;
        }

        return array_values(array_unique($addresses));
    }

    /** Canonical form; IPv4-mapped IPv6 addresses are classified as IPv4. */
    private function normalizeIp(string $value): ?string
    {
        if (filter_var($value, FILTER_VALIDATE_IP) === false || ($packed = inet_pton($value)) === false) {
            return null;
        }
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            $packed = substr($packed, 12);
        }

        $normalized = inet_ntop($packed);
        return $normalized === false ? null : strtolower($normalized);
    }

    /** @param list<string> $networks */
    private function matchesAny(string $address, array $networks): bool
    {
        $packed = (string)inet_pton($address);
        foreach ($networks as $network) {
            [$base, $bits] = explode('/', $network);
            $networkPacked = (string)inet_pton($base);
            if (strlen($networkPacked) !== strlen($packed)) continue;

            $bits = (int)$bits;
            $bytes = intdiv($bits, 8);
            if (substr($packed, 0, $bytes) !== substr($networkPacked, 0, $bytes)) continue;
            $remaining = $bits % 8;
            if ($remaining === 0) return true;
            $mask = (0xFF << (8 - $remaining)) & 0xFF;
            if ((ord($packed[$bytes]) & $mask) === (ord($networkPacked[$bytes]) & $mask)) return true;
        }

        return false;
    }
}
