<?php

namespace Tests\Unit;

use App\Services\MailConfigurationException;
use App\Services\MailEndpointPolicy;
use App\Services\SettingsInputPolicy;
use App\Services\SettingsValidationException;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * SMTP is the one outbound connection the web settings choose freely, so it
 * must not reach cloud metadata, and internal hosts only by operator choice.
 */
final class MailEndpointPolicyTest extends CIUnitTestCase
{
    private string|false $environment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->environment = getenv('EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS');
        putenv('EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS');
    }

    protected function tearDown(): void
    {
        putenv($this->environment === false ? 'EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS' : 'EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS=' . $this->environment);
        parent::tearDown();
    }

    public static function alwaysDenied(): iterable
    {
        foreach (['169.254.169.254', '::ffff:169.254.169.254', 'fd00:ec2::254', '100.100.100.200', 'fe80::1',
            '0.0.0.0', '224.0.0.1', 'ff02::1', '::'] as $ip) yield $ip => [$ip];
    }

    #[DataProvider('alwaysDenied')]
    public function testMetadataAndNonRoutableTargetsAreAlwaysDenied(string $ip): void
    {
        putenv('EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS=1');
        $this->assertDenied($this->policy(['metadata.example.test' => [$ip]]), 'metadata.example.test', 'smtp_target_denied');
        $this->assertDenied($this->policy([]), $ip, 'smtp_target_denied');
    }

    public static function internal(): iterable
    {
        foreach (['127.0.0.1', '::1', '10.1.2.3', '172.16.0.5', '192.168.1.10', '100.64.0.1', 'fd12:3456::1'] as $ip) yield $ip => [$ip];
    }

    #[DataProvider('internal')]
    public function testInternalTargetsNeedOperatorOptIn(string $ip): void
    {
        $policy = $this->policy(['relay.example.test' => [$ip]]);
        $this->assertDenied($policy, 'relay.example.test', 'smtp_target_denied');
        $this->assertDenied($policy, $ip, 'smtp_target_denied');

        putenv('EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS=1');
        $policy->assertAllowed('relay.example.test');
        $policy->assertAllowed($ip);
        $this->assertTrue(true);
    }

    public function testEveryResolvedAddressMustBeAllowed(): void
    {
        $this->assertDenied($this->policy(['mixed.example.test' => ['93.184.216.34', '10.0.0.1']]), 'mixed.example.test', 'smtp_target_denied');
        $this->policy(['smtp.example.test' => ['93.184.216.34', '2606:2800:220:1::1']])->assertAllowed('smtp.example.test');
        $this->assertDenied($this->policy([]), 'unknown.example.test', 'smtp_target_unresolved');
    }

    public function testSavingAnInternalAddressLiteralIsRejected(): void
    {
        $this->expectException(SettingsValidationException::class);
        $this->expectExceptionCode(422);
        (new SettingsInputPolicy())->normalize(['smtp_host' => '169.254.169.254'], [
            'email_protocol' => 'smtp', 'smtp_host' => '', 'smtp_port' => 587, 'smtp_user' => '', 'smtp_pass' => '',
            'smtp_crypto' => 'tls', 'email_from' => 'noreply@example.test', 'email_from_name' => 'eXtplorer',
        ]);
    }

    private function policy(array $records): MailEndpointPolicy
    {
        return new MailEndpointPolicy(static fn(string $host): array => $records[$host] ?? []);
    }

    private function assertDenied(MailEndpointPolicy $policy, string $host, string $reason): void
    {
        try {
            $policy->assertAllowed($host);
            $this->fail("SMTP target {$host} was allowed.");
        } catch (MailConfigurationException $exception) {
            $this->assertSame($reason, $exception->reason, $host);
        }
    }
}
