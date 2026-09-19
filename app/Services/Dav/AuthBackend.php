<?php

namespace App\Services\Dav;

use Sabre\DAV\Auth\Backend\AbstractBasic;

class AuthBackend extends AbstractBasic
{
    private string $passwordHash;

    public function __construct(private string $expectedUsername, string $expectedPassword)
    {
        $this->passwordHash = hash('sha256', $expectedPassword);
    }

    /**
     * Validates a username and password
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected function validateUserPass($username, $password): bool
    {
        return hash_equals($this->expectedUsername, (string)$username)
            && hash_equals($this->passwordHash, hash('sha256', (string)$password));
    }
}
