<?php

namespace App\Services;

final class AdminDelegationException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Administrative delegation is outside your permissions or home directory.', 403);
    }
}
