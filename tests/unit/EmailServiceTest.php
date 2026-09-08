<?php

namespace Tests\Unit;

use App\Services\EmailService;
use App\Services\SettingsService;
use CodeIgniter\Test\CIUnitTestCase;

class EmailServiceTest extends CIUnitTestCase
{
    private string $settingsFile;
    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsFile = config('Storage')->state . '/settings.php';
        $this->backup = is_file($this->settingsFile) ? file_get_contents($this->settingsFile) : null;
        if (is_file($this->settingsFile)) {
            unlink($this->settingsFile);
        }
    }

    protected function tearDown(): void
    {
        if ($this->backup === null) {
            @unlink($this->settingsFile);
        } else {
            file_put_contents($this->settingsFile, $this->backup);
        }

        parent::tearDown();
    }

    public function testDeliveryIsNotReadyUntilTheCurrentConfigurationWasTested(): void
    {
        $service = new EmailService();
        $settings = (new SettingsService())->getSettings();

        $this->assertFalse($service->isDeliveryReady($settings));

        $settings['smtp_host'] = 'smtp.example.test';
        $settings['email_delivery_verified_fingerprint'] = $service->configFingerprint($settings);
        $settings['email_delivery_verified_at'] = gmdate(DATE_ATOM);

        $this->assertTrue($service->isDeliveryReady($settings));
    }

    public function testChangingAnyEffectiveMailSettingInvalidatesVerification(): void
    {
        $service = new EmailService();
        $settings = (new SettingsService())->getSettings();
        $settings['smtp_host'] = 'smtp.example.test';
        $settings['email_delivery_verified_fingerprint'] = $service->configFingerprint($settings);
        $settings['email_delivery_verified_at'] = gmdate(DATE_ATOM);

        $settings['smtp_port'] = 465;

        $this->assertFalse($service->isDeliveryReady($settings));
    }

    public function testSuccessfulVerificationDoesNotExpireWithTime(): void
    {
        $service = new EmailService();
        $settings = (new SettingsService())->getSettings();
        $settings['smtp_host'] = 'smtp.example.test';
        $settings['email_delivery_verified_fingerprint'] = $service->configFingerprint($settings);
        $settings['email_delivery_verified_at'] = '2020-01-01T00:00:00+00:00';

        $this->assertTrue($service->isDeliveryReady($settings));
    }
}
