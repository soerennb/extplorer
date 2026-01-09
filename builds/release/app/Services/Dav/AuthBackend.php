<?php

namespace App\Services\Dav;

use Sabre\DAV\Auth\Backend\AbstractBasic;
use App\Models\UserModel;

class AuthBackend extends AbstractBasic
{
    /**
     * Validates a username and password
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected function validateUserPass($username, $password)
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

    public function getCurrentUser()
    {
        return $this->currentUser ?? null;
    }
}
