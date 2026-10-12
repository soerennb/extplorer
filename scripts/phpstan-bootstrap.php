<?php

// Static analysis needs framework paths and helper declarations, not a running
// application (which would initialize sessions and mutable installation state).
$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
foreach (['ROOTPATH' => $root, 'APPPATH' => $root . 'app/', 'SYSTEMPATH' => $root . 'vendor/codeigniter4/framework/system/',
    'FCPATH' => $root . 'public/', 'WRITEPATH' => $root . 'writable/', 'ENVIRONMENT' => 'development',
    'CI_DEBUG' => false] as $name => $value) {
    if (!defined($name)) define($name, $value);
}
