<?php
echo "User: " . posix_getpwuid(posix_geteuid())['name'] . "\n";
echo "Groups: " . implode(', ', posix_getgroups()) . "\n";
$path = '/mnt/c/wwwroot/eshop-laravel/';
echo "Is dir $path: " . (is_dir($path) ? 'Yes' : 'No') . "\n";

