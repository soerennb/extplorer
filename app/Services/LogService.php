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
        $logs = self::getLogs();
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
            'path' => $path,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ];
        
        array_unshift($logs, $entry);
        
        // Keep last N logs based on settings.
        if (count($logs) > $retention) {
            $logs = array_slice($logs, 0, $retention);
        }
        
        self::saveLogs($logs);
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
}
