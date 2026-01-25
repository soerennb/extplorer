<?php

namespace App\Services;

class LogService
{
    private static string $filePath = WRITEPATH . 'activity_logs.php';

    public static function log(string $action, string $path = '', string $details = '', ?string $username = null)
    {
        // Migration check on write (lazy)
        if (file_exists(WRITEPATH . 'activity_logs.json') && !file_exists(self::$filePath)) {
            $data = json_decode(file_get_contents(WRITEPATH . 'activity_logs.json'), true) ?? [];
            self::saveLogs($data);
            unlink(WRITEPATH . 'activity_logs.json');
        }

        $logs = self::getLogs();
        $entry = [
            'timestamp' => time(),
            'user' => $username ?? session('username') ?? 'System',
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
        
        self::saveLogs($logs);
    }

    public static function getLogs(): array
    {
        // Migration check on read
        if (file_exists(WRITEPATH . 'activity_logs.json') && !file_exists(self::$filePath)) {
            $data = json_decode(file_get_contents(WRITEPATH . 'activity_logs.json'), true) ?? [];
            self::saveLogs($data);
            unlink(WRITEPATH . 'activity_logs.json');
        }

        if (!file_exists(self::$filePath)) return [];
        
        $content = file_get_contents(self::$filePath);
        if (strpos($content, '<?php') === 0) {
            $content = str_replace('<?php die("Access denied"); ?>' . PHP_EOL, '', $content);
        }
        
        return json_decode($content, true) ?? [];
    }

    private static function saveLogs(array $logs): void
    {
        $content = '<?php die("Access denied"); ?>' . PHP_EOL . json_encode($logs, JSON_PRETTY_PRINT);
        file_put_contents(self::$filePath, $content);
    }
}
