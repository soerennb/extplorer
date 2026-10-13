<?php

namespace Tests\Unit;

use App\Services\TransferPolicy;
use CodeIgniter\Test\CIUnitTestCase;

class TransferPolicyTest extends CIUnitTestCase
{
    public function testNormalizeRecipientsFiltersInvalidAndDedupes(): void
    {
        $controller = new TransferPolicy();
        $result = $controller->recipients([
            'USER@example.com',
            'bad-email',
            'user@example.com',
            ' second@example.com ',
            '',
        ]);

        $this->assertSame(['user@example.com', 'second@example.com'], $result);
    }

    public function testNormalizeRecipientsCapsAt25(): void
    {
        $controller = new TransferPolicy();
        $emails = [];
        for ($i = 0; $i < 40; $i++) {
            $emails[] = "user{$i}@example.com";
        }

        $result = $controller->recipients($emails);
        $this->assertCount(25, $result);
        $this->assertSame('user0@example.com', $result[0]);
        $this->assertSame('user24@example.com', $result[24]);
    }

    public function testClampExpiryDaysRespectsBounds(): void
    {
        $controller = new TransferPolicy();

        $low = $controller->expiryDays(-5);
        $high = $controller->expiryDays(365);
        $mid = $controller->expiryDays(7);

        $this->assertGreaterThanOrEqual(1, $low);
        $this->assertLessThanOrEqual(30, $low);
        $this->assertSame(30, $high);
        $this->assertSame(7, $mid);
    }

    public function testNormalizeSessionIdStripsUnsafeChars(): void
    {
        $controller = new TransferPolicy();
        $result = $controller->sessionId('abc-123_!?4567890123');
        $this->assertSame('abc1234567890123', $result);

        $this->assertSame('', $controller->sessionId('abc-123_!?'));
    }

    public function testTransferFilenameRejectsPathAndControlCharacters(): void
    {
        $controller = new TransferPolicy();

        $this->expectException(\RuntimeException::class);
        $controller->filename("..\\secret\n.txt");
    }

    public function testTransferFilenameKeepsOnlyTheFinalPathComponent(): void
    {
        $controller = new TransferPolicy();

        $result = $controller->filename('/tmp/example.txt');

        $this->assertSame('example.txt', $result);
    }
}
