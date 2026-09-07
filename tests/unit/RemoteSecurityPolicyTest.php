<?php

namespace Tests\Unit;

use App\Services\RemoteSecurityPolicy;
use App\Services\VFS\RemotePathPolicy;
use CodeIgniter\Test\CIUnitTestCase;

class RemoteSecurityPolicyTest extends CIUnitTestCase
{
    private string|false $previousMode = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousMode = getenv('EXTPLORER_REMOTE_SECURITY_MODE');
    }

    protected function tearDown(): void
    {
        if ($this->previousMode === false) {
            putenv('EXTPLORER_REMOTE_SECURITY_MODE');
        } else {
            putenv('EXTPLORER_REMOTE_SECURITY_MODE=' . $this->previousMode);
        }
        parent::tearDown();
    }

    public function testStrictModeRejectsPlainFtp(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=strict');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Plain FTP is disabled');
        (new RemoteSecurityPolicy())->assertProtocolAllowed('ftp');
    }

    public function testStrictModeRequiresSftpFingerprint(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=strict');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('host-key fingerprint is required');
        (new RemoteSecurityPolicy())->assertProtocolAllowed('sftp');
    }

    public function testStrictModeRequiresVerifiedFtpsCertificate(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=strict');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('certificate verification is required');
        (new RemoteSecurityPolicy())->assertProtocolAllowed('ftps');
    }

    public function testStrictModeAcceptsFtpsOnlyAfterVerification(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=strict');
        (new RemoteSecurityPolicy())->assertProtocolAllowed('ftps', ['tls_verified' => true]);
        $this->assertTrue(true);
    }

    public function testSpkiPinAcceptsHexAndBase64Forms(): void
    {
        $policy = new RemoteSecurityPolicy();
        $expected = str_repeat('00', 32);
        $this->assertSame($expected, $policy->normalizeTlsSpkiPin($expected));
        $this->assertSame($expected, $policy->normalizeTlsSpkiPin('sha256/' . base64_encode(str_repeat("\0", 32))));
    }

    public function testCompatibilityModeAcceptsLegacyRemoteConfiguration(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=compat');
        (new RemoteSecurityPolicy())->assertProtocolAllowed('ftp');
        $this->assertSame('aabbcc', (new RemoteSecurityPolicy())->normalizeFingerprint('AA:BB:CC'));
    }

    public function testRemotePathPolicyRejectsTraversal(): void
    {
        $this->expectException(\RuntimeException::class);
        RemotePathPolicy::normalizeRelative('incoming/../../etc');
    }
}
