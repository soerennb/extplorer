<?php

// Test server for an extracted native archive. No application code comes from the checkout.
$public = rtrim((string)getenv('NATIVE_PUBLIC'), '/');
$prefix = (string)getenv('NATIVE_PREFIX');
$path = (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!str_starts_with($path, $prefix)) {
    http_response_code(404);
    exit;
}
$relative = substr($path, strlen($prefix));
$static = realpath($public . '/' . $relative);
if ($static !== false && str_starts_with($static, $public . '/') && is_file($static) && pathinfo($static, PATHINFO_EXTENSION) !== 'php') {
    $types = ['js' => 'text/javascript', 'css' => 'text/css', 'json' => 'application/json', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'woff2' => 'font/woff2'];
    header('Content-Type: ' . ($types[pathinfo($static, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($static);
    exit;
}
// The isolated auth fixture uses a real TLS proxy bound to loopback.
if (getenv('NATIVE_TLS') === '1') $_SERVER['HTTPS'] = 'on';
$_SERVER['SCRIPT_NAME'] = $prefix . 'index.php';
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
$_SERVER['SCRIPT_FILENAME'] = $public . '/index.php';
$_SERVER['DOCUMENT_ROOT'] = $public;
require $public . '/index.php';
