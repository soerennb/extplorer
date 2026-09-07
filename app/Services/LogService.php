<?php

namespace App\Services;

class LogService
{
    private static ?string $filePath = null;

    private static function filePath(): string
    {
        return self::$filePath ??= config('Storage')->logs . '/activity_logs.php';
    }

    public static function log(string $action, string $path = '', string $details = '', ?string $username = null)
    {
        $settingsService = new SettingsService();
        $retention = (int)($settingsService->get('log_retention_count', 500));
        if ($retention < 100) {
            $retention = 100;
        }
        if ($retention > 20000) {
            $retention = 20000;
        }
        $entry = [
            'timestamp' => time(),
            'user' => $username ?? session('username') ?? 'System',
            'action' => $action,
            'path' => self::redact($path),
            'details' => self::redact($details),
            'ip' => self::clientIp(),
            'schema_version' => 1,
            'event_id' => bin2hex(random_bytes(16)),
            'request_id' => self::requestId(),
        ];
        
        AtomicFileStore::transaction(self::filePath(), function (array &$logs) use ($entry, $retention): void {
            array_unshift($logs, $entry);

            // Keep last N logs based on settings.
            if (count($logs) > $retention) {
                $logs = array_slice($logs, 0, $retention);
            }
        });
    }

    /**
     * Write a structured security/operations event while retaining the
     * legacy activity-log fields used by the UI.
     *
     * @param array<string, mixed> $context
     */
    public static function event(string $event, string $outcome, array $context = [], ?string $username = null): void
    {
        $path = is_string($context['path'] ?? null) ? (string)$context['path'] : '';
        unset($context['path']);
        $context['outcome'] = $outcome;
        self::log($event, $path, (string)json_encode(self::redactValue($context), JSON_UNESCAPED_SLASHES), $username);
    }

    public static function getLogs(): array
    {
        return AtomicFileStore::read(self::filePath());
    }

    /**
     * Query logs with filters and pagination.
     *
     * Supported filters:
     * - user
     * - action
     * - path_contains
     * - date_from (timestamp or strtotime-compatible string)
     * - date_to (timestamp or strtotime-compatible string)
     */
    public static function queryLogs(array $filters = [], int $page = 1, int $pageSize = 50): array
    {
        $logs = self::getLogs();

        $userFilter = isset($filters['user']) ? trim((string)$filters['user']) : '';
        $actionFilter = isset($filters['action']) ? trim((string)$filters['action']) : '';
        $pathContains = isset($filters['path_contains']) ? trim((string)$filters['path_contains']) : '';
        $dateFrom = self::normalizeTimestamp($filters['date_from'] ?? null, false);
        $dateTo = self::normalizeTimestamp($filters['date_to'] ?? null, true);

        $filtered = array_values(array_filter($logs, static function (array $log) use ($userFilter, $actionFilter, $pathContains, $dateFrom, $dateTo): bool {
            $timestamp = (int)($log['timestamp'] ?? 0);

            if ($userFilter !== '' && strcasecmp((string)($log['user'] ?? ''), $userFilter) !== 0) {
                return false;
            }

            if ($actionFilter !== '' && stripos((string)($log['action'] ?? ''), $actionFilter) === false) {
                return false;
            }

            if ($pathContains !== '' && stripos((string)($log['path'] ?? ''), $pathContains) === false) {
                return false;
            }

            if ($dateFrom !== null && $timestamp < $dateFrom) {
                return false;
            }

            if ($dateTo !== null && $timestamp > $dateTo) {
                return false;
            }

            return true;
        }));

        if ($page < 1) {
            $page = 1;
        }
        if ($pageSize < 1) {
            $pageSize = 50;
        }
        if ($pageSize > 200) {
            $pageSize = 200;
        }

        $total = count($filtered);
        $offset = ($page - 1) * $pageSize;
        $items = array_slice($filtered, $offset, $pageSize);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $pageSize > 0 ? (int)ceil($total / $pageSize) : 1,
        ];
    }

    private static function normalizeTimestamp($value, bool $endOfDay): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $ts = (int)$value;
            return $ts > 0 ? $ts : null;
        }

        $str = trim((string)$value);
        if ($str === '') {
            return null;
        }

        if ($endOfDay && preg_match('/^\d{4}-\d{2}-\d{2}$/', $str) === 1) {
            $str .= ' 23:59:59';
        }

        $ts = strtotime($str);
        if ($ts === false) {
            return null;
        }
        return $ts;
    }

    private static function saveLogs(array $logs): void
    {
        AtomicFileStore::write(self::filePath(), $logs);
    }

    private static function clientIp(): string
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    private static function requestId(): string
    {
        $candidate = (string)($_SERVER['HTTP_X_REQUEST_ID'] ?? '');
        if ($candidate !== '' && strlen($candidate) <= 128 && preg_match('/\A[a-zA-Z0-9._:-]+\z/', $candidate) === 1) {
            return $candidate;
        }
        return bin2hex(random_bytes(16));
    }

    private static function redact(string $value): string
    {
        $value = (string)preg_replace(
            '/(password|passphrase|secret|token|authorization|private[_-]?key|encryption[_-]?key)\s*[=:]\s*[^,\s;]+/i',
            '$1=[REDACTED]',
            $value
        );
        return strlen($value) > 2000 ? substr($value, 0, 2000) . '…' : $value;
    }

    private static function redactValue(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && preg_match('/password|passphrase|secret|token|authorization|private[_-]?key|encryption[_-]?key/i', $key) === 1) {
            return '[REDACTED]';
        }
        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $childKey => $childValue) {
                $redacted[(string)$childKey] = self::redactValue($childValue, (string)$childKey);
            }
            return $redacted;
        }
        return is_string($value) ? self::redact($value) : $value;
    }
}
