<?php

namespace Tests\Unit;

use App\Services\EmailService;
use App\Services\MailConfigPolicy;
use App\Services\MailConfigurationException;
use App\Services\SendmailPolicy;
use App\Services\SettingsInputPolicy;
use App\Services\SettingsService;
use App\Services\SettingsValidationException;
use CodeIgniter\Email\Email;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class MailSecurityTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string|false $originalPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        $this->originalPath = getenv('EXTPLORER_SENDMAIL_PATH');
        putenv('EXTPLORER_SENDMAIL_PATH');
    }

    protected function tearDown(): void
    {
        putenv($this->originalPath === false ? 'EXTPLORER_SENDMAIL_PATH' : 'EXTPLORER_SENDMAIL_PATH=' . $this->originalPath);
        Services::resetSingle('email');
        $this->sandbox->close();
        parent::tearDown();
    }

    public static function invalidMailFields(): iterable
    {
        yield 'protocol injection' => [['email_protocol' => 'smtp;mail']];
        yield 'protocol type' => [['email_protocol' => []]];
        yield 'null protocol' => [['email_protocol' => null]];
        yield 'TLS typo' => [['smtp_crypto' => 'tlss']];
        yield 'port zero' => [['smtp_port' => 0]];
        yield 'port overflow' => [['smtp_port' => 65536]];
        yield 'fractional port' => [['smtp_port' => 1.5]];
        yield 'stream wrapper' => [['smtp_host' => 'unix:///tmp/socket']];
        yield 'host includes port' => [['smtp_host' => 'server:25']];
        yield 'header in sender' => [['email_from' => "test@example.com\r\nBcc:other@example.com"]];
        yield 'header in name' => [['email_from_name' => "Name\r\nBcc: other@example.com"]];
    }

    #[DataProvider('invalidMailFields')]
    public function testInvalidMailFieldsNeverReachDelivery(array $fields): void
    {
        $this->expectException(MailConfigurationException::class);
        (new MailConfigPolicy())->transportConfig(array_replace($this->smtp(), $fields));
    }

    public function testNormalizationIsSharedByFingerprintAndActualTransport(): void
    {
        $settings = $this->smtp();
        $variant = array_replace($settings, ['email_protocol' => ' SMTP ', 'smtp_crypto' => ' TLS ',
            'smtp_host' => ' smtp.example.test ', 'smtp_port' => '587']);
        $canonical = (new SettingsInputPolicy())->normalize($variant, []);
        $this->assertSame('tls', $canonical['smtp_crypto']);
        $config = (new MailConfigPolicy())->transportConfig($variant);
        $this->assertSame('tls', $config['SMTPCrypto']);
        $this->assertSame('smtp', $config['protocol']);
        $this->assertSame((new EmailService())->configFingerprint($settings), (new EmailService())->configFingerprint($variant));
        $this->assertSame(' sensitive password ', $config['SMTPPass']);
    }

    public function testPhpMailRemainsAnExplicitTransport(): void
    {
        $config = (new MailConfigPolicy())->transportConfig(array_replace($this->smtp(), ['email_protocol' => 'mail']));
        $this->assertSame('mail', $config['protocol']);
        $this->assertArrayNotHasKey('mailPath', $config);
        $this->assertArrayNotHasKey('SMTPHost', $config);
    }

    public function testAReadOnlyPathCannotBeWrittenEvenUsingNull(): void
    {
        $this->expectException(SettingsValidationException::class);
        $this->expectExceptionCode(422);
        (new SettingsInputPolicy())->normalize(['sendmail_path' => null], $this->smtp());
    }

    public static function blockedSendmailPrograms(): iterable
    {
        yield 'legacy uploaded program' => [false, 'uploaded-program', 'sendmail_migration_required'];
        yield 'operator points to writable program' => [true, 'uploaded-program', 'sendmail_unavailable'];
        yield 'shell metacharacters in filename' => [true, 'mailer;echo injected', 'sendmail_unavailable'];
        yield 'read-only file still owned by PHP' => [true, 'readonly-program', 'sendmail_unavailable'];
        yield 'symlink through writable directory' => [true, 'linked-program', 'sendmail_unavailable'];
    }

    #[DataProvider('blockedSendmailPrograms')]
    public function testUploadedExecutablesAreBlockedOnEveryMailEntryPoint(bool $operator, string $name, string $reason): void
    {
        $program = $this->sandbox->root . '/files/' . $name;
        $marker = $this->sandbox->root . '/executed';
        if ($name === 'linked-program') {
            symlink('/usr/bin/true', $program);
        } else {
            file_put_contents($program, "#!/bin/sh\nprintf executed > " . escapeshellarg($marker) . "\n");
            chmod($program, $name === 'readonly-program' ? 0500 : 0700);
        }
        if ($operator) putenv('EXTPLORER_SENDMAIL_PATH=' . $program);
        $settings = array_replace($this->smtp(), ['email_protocol' => 'sendmail', 'sendmail_path' => $program]);
        (new SettingsService())->saveSettings($settings);
        $email = $this->createMock(Email::class);
        $email->expects($this->never())->method('initialize');
        $email->expects($this->never())->method('send');
        Services::injectMock('email', $email);
        $service = new EmailService();
        $this->assertFalse($service->isConfigured($settings));
        $this->assertSame($reason, $service->deliveryStatus($settings)['reason']);
        $this->assertSame($reason, $service->validateConfig($settings)['error']);
        $this->assertFalse($service->sendTestEmail('recipient@example.test'));
        $this->assertFalse($service->sendTestEmailWithConfig('recipient@example.test', $settings)['ok']);
        $this->assertFalse($service->sendTransferNotification([], 'https://example.test/s/test'));
        $this->assertFalse($service->sendDownloadNotification(['sender_email' => 'recipient@example.test']));
        $this->assertFalse($service->sendExpiryReminder(['sender_email' => 'recipient@example.test']));
        $this->assertFileDoesNotExist($marker);
    }

    public function testAnExplicitTrustedOperatorPathOverridesAnUntrustedPersistedValue(): void
    {
        if (!function_exists('posix_geteuid') || posix_geteuid() === 0 || !is_file('/usr/bin/true')) {
            $this->markTestSkipped('Requires a non-root POSIX runtime and a trusted system executable.');
        }
        putenv('EXTPLORER_SENDMAIL_PATH=/usr/bin/true');
        $settings = array_replace($this->smtp(), ['email_protocol' => 'sendmail', 'sendmail_path' => '/tmp/old-untrusted-program']);
        $config = (new MailConfigPolicy())->transportConfig($settings);
        $this->assertSame(escapeshellarg(realpath('/usr/bin/true')), $config['mailPath']);
        $this->assertTrue((new EmailService())->validateConfig($settings)['ok']);
        // Execute only this harmless, operator-selected system program.
        $this->assertTrue((new EmailService())->sendTestEmailWithConfig('recipient@example.test', $settings)['ok']);
    }

    public function testPreFixVerificationCannotAuthorizeTheNewMailTrustContract(): void
    {
        $settings = $this->smtp();
        $settings['email_delivery_verified_fingerprint'] = hash('sha256', json_encode($settings));
        $settings['email_delivery_verified_at'] = gmdate(DATE_ATOM);
        $this->assertFalse((new EmailService())->isDeliveryReady($settings));
    }

    public function testRawRemoteErrorsAreNotReturnedByValidation(): void
    {
        $email = $this->getMockBuilder(Email::class)->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'SMTPConnect', 'SMTPEnd', 'printDebugger'])->getMock();
        $email->method('SMTPConnect')->willThrowException(new \RuntimeException('password=secret internal-host'));
        $email->expects($this->once())->method('SMTPEnd');
        $email->expects($this->never())->method('printDebugger');
        Services::injectMock('email', $email);
        $this->assertSame(['ok' => false, 'error' => 'email_validation_failed'], (new EmailService())->validateConfig($this->smtp()));
    }

    private function smtp(): array
    {
        return ['email_protocol' => 'smtp', 'smtp_host' => 'smtp.example.test', 'smtp_port' => 587,
            'smtp_user' => 'test-user', 'smtp_pass' => ' sensitive password ', 'smtp_crypto' => 'tls',
            'email_from' => 'test@example.test', 'email_from_name' => 'Test'];
    }
}
