<?php

namespace App\Services;

use Redis;
use RuntimeException;

/**
 * Verifies the Redis endpoint selected for sessions or cache.
 */
final class RedisHealthService
{
    public function check(): void
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('Redis storage is configured but the PHP redis extension is unavailable.');
        }

        [$host, $port, $password, $database, $timeout] = $this->connectionSettings();
        $redis = new Redis();
        try {
            if (!$redis->connect($host, $port, $timeout)) {
                throw new RuntimeException('Unable to connect to the configured Redis endpoint.');
            }
            if ($password !== null && !$redis->auth($password)) {
                throw new RuntimeException('Unable to authenticate to the configured Redis endpoint.');
            }
            if (!$redis->select($database) || !in_array($redis->ping(), [true, '+PONG'], true)) {
                throw new RuntimeException('The configured Redis endpoint failed its health check.');
            }
        } finally {
            try {
                $redis->close();
            } catch (\Throwable) {
                // Closing an already failed connection is best effort.
            }
        }
    }

    /** @return array{0:string,1:int,2:string|null,3:int,4:float} */
    private function connectionSettings(): array
    {
        $url = trim((string)(getenv('EXTPLORER_REDIS_URL') ?: ''));
        $passwordFile = getenv('EXTPLORER_REDIS_PASSWORD_FILE');
        $password = $passwordFile !== false && trim($passwordFile) !== ''
            ? SecretReader::file(trim($passwordFile))
            : (getenv('EXTPLORER_REDIS_PASSWORD') ?: null);
        $database = (int)(getenv('EXTPLORER_REDIS_DATABASE') ?: 0);
        $timeout = (float)(getenv('EXTPLORER_REDIS_TIMEOUT') ?: 1.0);

        if ($url !== '') {
            $parts = parse_url($url);
            if ($parts === false || empty($parts['host'])) {
                throw new RuntimeException('EXTPLORER_REDIS_URL is invalid.');
            }
            parse_str((string)($parts['query'] ?? ''), $query);
            $password = isset($query['auth']) ? (string)$query['auth'] : $password;
            $database = isset($query['database']) ? (int)$query['database'] : $database;
            $timeout = isset($query['timeout']) ? (float)$query['timeout'] : $timeout;
            return [
                (string)$parts['host'],
                (int)($parts['port'] ?? 6379),
                $password,
                $database,
                $timeout,
            ];
        }

        return [
            (string)(getenv('EXTPLORER_REDIS_HOST') ?: '127.0.0.1'),
            (int)(getenv('EXTPLORER_REDIS_PORT') ?: 6379),
            $password,
            $database,
            $timeout,
        ];
    }
}
