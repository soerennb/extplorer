<?php

namespace Config;

/**
 * Builds a base URL from canonical web-server variables for native installs.
 */
final class NativeBaseUrlResolver
{
    /**
     * HTTP_HOST is intentionally ignored because it is supplied by the request.
     *
     * @param list<string> $trustedProxies
     * @return array{url: string, scheme: string, host: string}|null
     */
    public static function resolve(array $server, array $trustedProxies): ?array
    {
        $canonicalName = self::canonicalServerName($server['SERVER_NAME'] ?? null);
        if ($canonicalName === null) {
            return null;
        }

        $remoteAddress = $server['REMOTE_ADDR'] ?? null;
        $trustedProxy = is_string($remoteAddress)
            && self::isTrustedProxyAddress($remoteAddress, $trustedProxies);
        $scheme = self::serverRequestIsSecure($server, $trustedProxy) ? 'https' : 'http';
        $portValue = $trustedProxy ? null : ($server['SERVER_PORT'] ?? null);
        $port = self::validPort($portValue);
        if ($portValue !== null && $port === null) {
            return null;
        }

        $defaultPort = $scheme === 'https' ? 443 : 80;
        $authority = $canonicalName['authority'];
        if ($port !== null && $port !== $defaultPort) {
            $authority .= ':' . $port;
        }

        $scriptDirectory = self::scriptDirectory($server['SCRIPT_NAME'] ?? null);
        if ($scriptDirectory === null) {
            return null;
        }
        $path = $scriptDirectory === '' ? '/' : $scriptDirectory . '/';

        return [
            'url' => $scheme . '://' . $authority . $path,
            'scheme' => $scheme,
            'host' => $canonicalName['host'],
        ];
    }

    /** @return array{authority: string, host: string}|null */
    private static function canonicalServerName(mixed $value): ?array
    {
        if (!is_string($value) || $value === '' || trim($value) !== $value) {
            return null;
        }

        $ipv6 = $value;
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $ipv6 = substr($value, 1, -1);
        }
        if (filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $host = '[' . strtolower($ipv6) . ']';
            return ['authority' => $host, 'host' => $host];
        }

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return ['authority' => $value, 'host' => $value];
        }

        $host = strtolower($value);
        if (str_ends_with($host, '.')) {
            $host = substr($host, 0, -1);
        }
        if ($host === '' || strlen($host) > 253) {
            return null;
        }

        foreach (explode('.', $host) as $label) {
            if (strlen($label) > 63
                || preg_match('/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/', $label) !== 1
            ) {
                return null;
            }
        }

        return ['authority' => $host, 'host' => $host];
    }

    private static function serverRequestIsSecure(array $server, bool $trustedProxy): bool
    {
        $https = $server['HTTPS'] ?? null;
        if (is_string($https) && in_array(strtolower($https), ['on', '1', 'https'], true)) {
            return true;
        }

        $requestScheme = $server['REQUEST_SCHEME'] ?? null;
        if (is_string($requestScheme) && strtolower($requestScheme) === 'https') {
            return true;
        }

        $forwardedProtocol = $server['HTTP_X_FORWARDED_PROTO'] ?? null;
        return $trustedProxy
            && is_string($forwardedProtocol)
            && strtolower(trim($forwardedProtocol)) === 'https';
    }

    private static function validPort(mixed $value): ?int
    {
        if ((!is_string($value) && !is_int($value)) || preg_match('/\A[0-9]{1,5}\z/', (string)$value) !== 1) {
            return null;
        }

        $port = (int)$value;
        return $port >= 1 && $port <= 65535 ? $port : null;
    }

    private static function scriptDirectory(mixed $scriptName): ?string
    {
        if ($scriptName === null || $scriptName === '') {
            return '';
        }
        if (!is_string($scriptName)
            || !str_starts_with($scriptName, '/')
            || preg_match('/[\x00-\x20\x7f?#\\\\]/', $scriptName) === 1
            || preg_match('/%(?![A-Fa-f0-9]{2})/', $scriptName) === 1
        ) {
            return null;
        }

        foreach (explode('/', $scriptName) as $segment) {
            if (in_array(strtolower(rawurldecode($segment)), ['.', '..'], true)) {
                return null;
            }
        }

        $directory = dirname($scriptName);
        return $directory === '/' || $directory === '.' ? '' : rtrim($directory, '/');
    }

    /** @param list<string> $trustedProxies */
    private static function isTrustedProxyAddress(string $address, array $trustedProxies): bool
    {
        $binaryAddress = inet_pton($address);
        if ($binaryAddress === false) {
            return false;
        }

        foreach ($trustedProxies as $proxy) {
            [$network, $prefix] = array_pad(explode('/', $proxy, 2), 2, null);
            $binaryNetwork = inet_pton($network);
            if ($binaryNetwork === false || strlen($binaryAddress) !== strlen($binaryNetwork)) {
                continue;
            }
            if ($prefix === null) {
                if ($binaryAddress === $binaryNetwork) {
                    return true;
                }
                continue;
            }

            $prefixLength = (int)$prefix;
            $fullBytes = intdiv($prefixLength, 8);
            $remainingBits = $prefixLength % 8;
            if ($fullBytes > 0 && strncmp($binaryAddress, $binaryNetwork, $fullBytes) !== 0) {
                continue;
            }
            if ($remainingBits === 0
                || (ord($binaryAddress[$fullBytes]) & (0xFF & (0xFF << (8 - $remainingBits))))
                    === (ord($binaryNetwork[$fullBytes]) & (0xFF & (0xFF << (8 - $remainingBits))))
            ) {
                return true;
            }
        }

        return false;
    }
}
