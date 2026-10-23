<?php

namespace Tests\Unit;

use App\Services\MailRatePolicy;
use CodeIgniter\Test\CIUnitTestCase;

/** Application mail triggered by other people must not become a mail cannon. */
final class MailRatePolicyTest extends CIUnitTestCase
{
    public function testAnonymousDownloadsNotifyTheOwnerAtMostOncePerHourAndShare(): void
    {
        $policy = new MailRatePolicy();
        $share = bin2hex(random_bytes(16));

        $this->assertTrue($policy->allowDownloadNotification($share));
        $this->assertFalse($policy->allowDownloadNotification($share));
        $this->assertTrue($policy->allowDownloadNotification(bin2hex(random_bytes(16))));
    }

    public function testTransferRecipientsAreLimitedPerAccountAndHour(): void
    {
        $policy = new MailRatePolicy();
        $user = 'sender-' . bin2hex(random_bytes(4));

        for ($batch = 0; $batch < 4; $batch++) {
            $this->assertTrue($policy->allowTransferRecipients($user, 25), (string)$batch);
        }
        $this->assertFalse($policy->allowTransferRecipients($user, 1));
        $this->assertTrue($policy->allowTransferRecipients($user . '-other', 25));
    }
}
