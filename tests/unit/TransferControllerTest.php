<?php

namespace Tests\Unit;

use App\Controllers\TransferController;
use CodeIgniter\Test\CIUnitTestCase;

class TransferControllerTest extends CIUnitTestCase
{
    private function callPrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    public function testNormalizeRecipientsFiltersInvalidAndDedupes(): void
    {
        $controller = new TransferController();
        $result = $this->callPrivate($controller, 'normalizeRecipients', [[
            'USER@example.com',
            'bad-email',
            'user@example.com',
            ' second@example.com ',
            '',
        ]]);

        $this->assertSame(['user@example.com', 'second@example.com'], $result);
    }

    public function testNormalizeRecipientsCapsAt25(): void
    {
        $controller = new TransferController();
        $emails = [];
        for ($i = 0; $i < 40; $i++) {
            $emails[] = "user{$i}@example.com";
        }

        $result = $this->callPrivate($controller, 'normalizeRecipients', [$emails]);
        $this->assertCount(25, $result);
        $this->assertSame('user0@example.com', $result[0]);
        $this->assertSame('user24@example.com', $result[24]);
    }

    public function testClampExpiryDaysRespectsBounds(): void
    {
        $controller = new TransferController();

        $low = $this->callPrivate($controller, 'clampExpiryDays', [-5]);
        $high = $this->callPrivate($controller, 'clampExpiryDays', [365]);
        $mid = $this->callPrivate($controller, 'clampExpiryDays', [7]);

        $this->assertGreaterThanOrEqual(1, $low);
        $this->assertLessThanOrEqual(30, $low);
        $this->assertSame(30, $high);
        $this->assertSame(7, $mid);
    }

    public function testNormalizeSessionIdStripsUnsafeChars(): void
    {
        $controller = new TransferController();
        $result = $this->callPrivate($controller, 'normalizeSessionId', ['abc-123_!?']);
        $this->assertSame('abc123', $result);
    }
}

