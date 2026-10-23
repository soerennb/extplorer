<?php

namespace Tests\Unit;

use App\Services\PasswordPolicy;
use CodeIgniter\Test\CIUnitTestCase;

final class PasswordPolicyTest extends CIUnitTestCase
{
    public function testPasswordsLongerThanBcryptAcceptsAreRejected(): void
    {
        // bcrypt ignores everything after 72 bytes, so a longer passphrase
        // would silently accept any suffix.
        $this->assertNull(PasswordPolicy::validate(str_repeat('a', 72)));
        $this->assertNotNull(PasswordPolicy::validate(str_repeat('a', 73)));
        $this->assertNotNull(PasswordPolicy::validate(str_repeat('ä', 37)));
        $this->assertNotNull(PasswordPolicy::validate('short'));
    }
}
