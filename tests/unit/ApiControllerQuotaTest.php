<?php

namespace Tests\Unit;

use App\Controllers\ApiController;
use CodeIgniter\Test\CIUnitTestCase;

class ApiControllerQuotaTest extends CIUnitTestCase
{
    private string $homePath;

    private function callPrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $baseRoot = WRITEPATH . 'file_manager_root';
        $relativeHome = 'tests/quota-user';
        $this->homePath = $baseRoot . DIRECTORY_SEPARATOR . $relativeHome;

        if (is_dir($this->homePath)) {
            $this->rrmdir($this->homePath);
        }
        @mkdir($this->homePath, 0755, true);

        session()->set('home_dir', $relativeHome);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->homePath)) {
            $this->rrmdir($this->homePath);
        }
        parent::tearDown();
    }

    public function testExceedsMaxUploadSize(): void
    {
        $controller = new ApiController();
        $settings = ['upload_max_file_mb' => 1];

        $twoMb = 2 * 1024 * 1024;
        $result = $this->callPrivate($controller, 'exceedsMaxUploadSize', [$twoMb, $settings]);
        $this->assertTrue($result);
    }

    public function testWouldExceedUserQuota(): void
    {
        $controller = new ApiController();

        // Create ~1MB usage.
        $this->writeBytes($this->homePath . DIRECTORY_SEPARATOR . 'existing.bin', 1024 * 1024);

        $settings = [
            'quota_per_user_mb' => 2,
        ];

        $incomingTooBig = (int)(1.5 * 1024 * 1024);
        $incomingSmall = 512 * 1024;

        $tooBig = $this->callPrivate($controller, 'wouldExceedUserQuota', [$incomingTooBig, $settings]);
        $smallOk = $this->callPrivate($controller, 'wouldExceedUserQuota', [$incomingSmall, $settings]);

        $this->assertTrue($tooBig);
        $this->assertFalse($smallOk);
    }

    private function writeBytes(string $path, int $bytes): void
    {
        $chunk = str_repeat('A', 1024);
        $handle = fopen($path, 'wb');
        $remaining = $bytes;
        while ($remaining > 0) {
            $write = min(1024, $remaining);
            fwrite($handle, substr($chunk, 0, $write));
            $remaining -= $write;
        }
        fclose($handle);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path) && !is_link($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}

