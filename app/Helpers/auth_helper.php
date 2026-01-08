<?php

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        $permissions = session('permissions') ?? [];
        if (in_array('*', $permissions)) return true;
        return in_array($permission, $permissions);
    }
}
