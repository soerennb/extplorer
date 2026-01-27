<?php

namespace Tests\Unit;

use App\Services\LogService;
use App\Services\SettingsService;
use CodeIgniter\Test\CIUnitTestCase;

class LogServiceTest extends CIUnitTestCase
{
    private string $settingsPath;
    private string $logsPath;
    private ?string $settingsBackup = null;
    private ?string $logsBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsPath = WRITEPATH . 'settings.php';
        $this->logsPath = WRITEPATH . 'activity_logs.php';

        if (file_exists($this->settingsPath)) {
            $this->settingsBackup = file_get_contents($this->settingsPath) ?: '';
        }
        if (file_exists($this->logsPath)) {
            $this->logsBackup = file_get_contents($this->logsPath) ?: '';
        }
    }

    protected function tearDown(): void
    {
        if ($this->settingsBackup !== null) {
            file_put_contents($this->settingsPath, $this->settingsBackup);
        } elseif (file_exists($this->settingsPath)) {
            @unlink($this->settingsPath);
        }

        if ($this->logsBackup !== null) {
            file_put_contents($this->logsPath, $this->logsBackup);
        } elseif (file_exists($this->logsPath)) {
            @unlink($this->logsPath);
        }

        parent::tearDown();
    }

    public function testRetentionHonorsConfiguredLimit(): void
    {
        $settings = new SettingsService();
        $settings->saveSettings(['log_retention_count' => 120]);

        $this->writeProtectedJson($this->logsPath, []);

        for ($i = 0; $i < 130; $i++) {
            LogService::log('Test Action', "/tmp/{$i}", '', 'tester');
        }

        $logs = LogService::getLogs();
        $this->assertCount(120, $logs);
        $this->assertSame('/tmp/129', $logs[0]['path']);
    }

    public function testQueryLogsAppliesFiltersAndPagination(): void
    {
        $now = time();
        $logs = [
            [
                'timestamp' => $now - 86400 * 3,
                'user' => 'alice',
                'action' => 'Upload',
                'path' => '/data/a.txt',
                'details' => '',
                'ip' => '127.0.0.1',
            ],
            [
                'timestamp' => $now - 86400,
                'user' => 'bob',
                'action' => 'Delete',
                'path' => '/data/b.txt',
                'details' => '',
                'ip' => '127.0.0.2',
            ],
            [
                'timestamp' => $now,
                'user' => 'alice',
                'action' => 'Rename',
                'path' => '/data/c.txt',
                'details' => '',
                'ip' => '127.0.0.3',
            ],
        ];
        $this->writeProtectedJson($this->logsPath, $logs);

        $result = LogService::queryLogs(
            [
                'user' => 'alice',
                'date_from' => date('Y-m-d', $now - 86400 * 2),
            ],
            1,
            10
        );

        $this->assertSame(1, $result['total']);
        $this->assertSame('Rename', $result['items'][0]['action']);
        $this->assertSame(1, $result['totalPages']);
    }

    private function writeProtectedJson(string $path, array $data): void
    {
        $content = '<?php die("Access denied"); ?>' . PHP_EOL . json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($path, $content);
    }
}

