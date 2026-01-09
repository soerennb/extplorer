<?php

namespace App\Services\Dav;

use Sabre\DAV\Auth\Backend\AbstractBasic;
use App\Models\UserModel;

class AuthBackend extends AbstractBasic
{
    protected ?array $currentUser = null;

    /**
     * Validates a username and password
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected function validateUserPass($username, $password): bool
    {
        $userModel = new UserModel();
        $user = $userModel->verifyUser($username, $password);

        if ($user) {
            // We store the user data in the object for later use (e.g. home_dir)
            $this->currentUser = $user;
            return true;
        }

        return false;
    }

    public function getCurrentUser(): ?array
    {
        return $this->currentUser;
    }
}
