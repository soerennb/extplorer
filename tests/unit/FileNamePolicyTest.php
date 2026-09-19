<?php

namespace Tests\Unit;

use App\Services\FileNamePolicy;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

final class FileNamePolicyTest extends CIUnitTestCase
{
    public function testDangerousExecutableAndConfigurationNamesAreRejected(): void
    {
        $policy = new FileNamePolicy();

        foreach (['shell.php', '.php', 'payload.pHp', '.htaccess', 'script.cgi', 'file.php.'] as $filename) {
            try {
                $policy->assertSafe($filename);
                $this->fail("Expected {$filename} to be rejected.");
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    public function testOptionalAllowListCannotReenableHardDeniedName(): void
    {
        $policy = new FileNamePolicy();

        $this->assertFalse($policy->isAllowed('payload.php', ['php']));
        $this->assertFalse($policy->isAllowed('notes.txt', ['pdf']));
        $this->assertTrue($policy->isAllowed('notes.txt', ['txt']));
    }

    public function testWindowsAmbiguousNamesAreRejected(): void
    {
        $policy = new FileNamePolicy();

        foreach (['stream.txt::$DATA', 'trailing-space.txt ', 'trailing-dot.txt.', 'CON', 'NUL.txt', 'LPT1.log'] as $filename) {
            try {
                $policy->assertSafe($filename);
                $this->fail("Expected {$filename} to be rejected.");
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    public function testSafePathRejectsDangerousIntermediateComponents(): void
    {
        $policy = new FileNamePolicy();

        $this->expectException(RuntimeException::class);
        $policy->assertSafePath('nested/.htaccess/file.txt');
    }
}
