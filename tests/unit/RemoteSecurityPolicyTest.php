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
        $this->expectExceptionMessage('actual connection can be authenticated');
        (new RemoteSecurityPolicy())->assertProtocolAllowed('ftps');
    }

    public function testStrictModeRejectsUnboundPreflightClaims(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=strict');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('actual connection can be authenticated');
        (new RemoteSecurityPolicy())->assertProtocolAllowed('ftps', ['tls_verified' => true]);
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

    public function testNegotiatedHostKeyAcceptsSha256Base64AndRejectsDifferentKey(): void
    {
        putenv('EXTPLORER_REMOTE_SECURITY_MODE=strict');
        $raw = "key bytes with mixed case\0\xff";
        $public = 'ssh-ed25519 ' . base64_encode($raw);
        $pin = 'SHA256:' . rtrim(base64_encode(hash('sha256', $raw, true)), '=');
        $policy = new RemoteSecurityPolicy();
        $this->assertSame(hash('sha256', $raw), $policy->normalizeFingerprint($pin));
        $policy->assertSshHostKey($public, $pin);
        $policy->assertSshHostKey($public, hash('sha256', $raw));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fingerprint verification failed');
        $policy->assertSshHostKey('ssh-ed25519 ' . base64_encode('different key'), $pin);
    }

    public function testRemotePathPolicyRejectsTraversal(): void
    {
        $this->expectException(\RuntimeException::class);
        RemotePathPolicy::normalizeRelative('incoming/../../etc');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidRemotePathProvider')]
    public function testRemotePathPolicyRejectsAmbiguousPathForms(string $path): void
    {
        $this->expectException(\RuntimeException::class);
        RemotePathPolicy::normalizeRelative($path);
    }

    public static function invalidRemotePathProvider(): array
    {
        return [
            'control character' => ["safe/secret\n.txt"],
            'drive relative path' => ['C:Windows/system.ini'],
            'unc path' => ['//server/share/file.txt'],
        ];
    }
}
