<?php

namespace App\Services;

class LogService
{
    private static string $filePath = WRITEPATH . 'activity_logs.json';

    public static function log(string $action, string $path = '', string $details = '')
    {
        $logs = self::getLogs();
        $entry = [
            'timestamp' => time(),
            'user' => session('username') ?? 'System',
            'action' => $action,
            'path' => $path,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ];
        
        array_unshift($logs, $entry);
        
        // Keep last 500 logs
        if (count($logs) > 500) {
            $logs = array_slice($logs, 0, 500);
        }
        
        file_put_contents(self::$filePath, json_encode($logs, JSON_PRETTY_PRINT));
    }

    public static function getLogs(): array
    {
        if (!file_exists(self::$filePath)) return [];
        return json_decode(file_get_contents(self::$filePath), true) ?? [];
    }
}
