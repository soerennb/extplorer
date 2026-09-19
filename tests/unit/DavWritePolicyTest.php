<?php

namespace Tests\Unit;

use App\Services\DavWritePolicy;
use CodeIgniter\Test\CIUnitTestCase;

final class DavWritePolicyTest extends CIUnitTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/extplorer-dav-policy-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/*') ?: [] as $path) @unlink($path);
        @rmdir($this->root);
        parent::tearDown();
    }

    public function testAtomicWriteEnforcesExtensionAndStreamingSizeLimit(): void
    {
        $policy = new DavWritePolicy('alice', $this->root, 'txt', 'exe', 5, 0);
        $this->assertSame(5, $policy->writeAtomic($this->root . '/ok.txt', 'hello'));

        try {
            $policy->writeAtomic($this->root . '/blocked.exe', 'x');
            $this->fail('Blocked extension was accepted.');
        } catch (\RuntimeException) {
            $this->assertFileDoesNotExist($this->root . '/blocked.exe');
        }

        try {
            $policy->writeAtomic($this->root . '/large.txt', '123456');
            $this->fail('Oversized stream was accepted.');
        } catch (\RuntimeException) {
            $this->assertFileDoesNotExist($this->root . '/large.txt');
        }
    }

    public function testQuotaAccountsForOverwriteSize(): void
    {
        file_put_contents($this->root . '/existing.txt', '1234');
        $policy = new DavWritePolicy('alice', $this->root, 'txt', '', 100, 5);
        $this->assertSame(5, $policy->writeAtomic($this->root . '/existing.txt', '12345'));

        $this->expectException(\RuntimeException::class);
        $policy->writeAtomic($this->root . '/second.txt', 'x');
    }
}
