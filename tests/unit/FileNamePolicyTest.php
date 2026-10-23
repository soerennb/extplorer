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

        foreach (['shell.php', '.php', 'payload.pHp', '.htaccess', '.htpasswd', 'script.cgi', 'file.php.'] as $filename) {
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

    public function testInvisibleAndBidiControlCharactersAreRejected(): void
    {
        $policy = new FileNamePolicy();
        foreach (["rechnung\u{202E}fdp.html", "a\u{2066}b.txt", "a\u{200F}.txt", "zero\u{200B}width.txt",
            "bom\u{FEFF}.txt", "line\u{2028}break.txt", "nel\u{0085}.txt", "soft\u{00AD}hyphen.txt", "a\u{061C}.txt"] as $name) {
            $this->assertFalse($policy->isAllowed($name), json_encode($name));
        }

        // Ordinary international names, including joiners in emoji and Persian, remain valid.
        foreach (['Müller Ä.txt', '日本語.pdf', "نامه\u{200C}ها.txt", "family \u{1F468}\u{200D}\u{1F469}.jpg"] as $name) {
            $this->assertSame($name, $policy->assertSafe($name));
        }
    }
}
