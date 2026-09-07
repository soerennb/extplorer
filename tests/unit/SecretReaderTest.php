<?php

namespace Tests\Unit;

use App\Services\SecretReader;
use CodeIgniter\Test\CIUnitTestCase;

class SecretReaderTest extends CIUnitTestCase
{
    private string $secretFile;

    protected function setUp(): void
    {
        parent::setUp();
        $secretFile = tempnam(sys_get_temp_dir(), 'extplorer-secret-');
        if ($secretFile === false) {
            $this->fail('Unable to create secret fixture.');
        }
        $this->secretFile = $secretFile;
    }

    protected function tearDown(): void
    {
        @unlink($this->secretFile);
        parent::tearDown();
    }

    public function testSecretFileRemovesOnlyTheTerminalNewline(): void
    {
        file_put_contents($this->secretFile, "  secret-value  \n");

        $this->assertSame('  secret-value  ', SecretReader::file($this->secretFile));
    }

    public function testEmptySecretFileIsRejected(): void
    {
        file_put_contents($this->secretFile, "\n");

        $this->expectException(\RuntimeException::class);
        SecretReader::file($this->secretFile);
    }
}
