<?php

declare(strict_types=1);

// Install on YOUR website. Bootstrap supplies logoutCurrentSession(), which destroys
// the current website login and clears its cookies. Never accept arbitrary callbacks.
require __DIR__ . '/site-bootstrap.php';

$callback = 'https://files.example.com/login/external-logout/callback';
$key = rtrim((string)file_get_contents('/run/secrets/website_signing_key'), "\r\n");
$state = $_POST['state'] ?? null;
$time = $_POST['time'] ?? null;
$signature = $_POST['signature'] ?? null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || strlen($key) < 32 || ($_POST['callback'] ?? null) !== $callback
    || !is_string($state) || preg_match('/\A[a-f0-9]{64}\z/', $state) !== 1
    || !is_string($time) || preg_match('/\A[0-9]{10}\z/', $time) !== 1 || abs(time() - (int)$time) > 60
    || !is_string($signature) || !hash_equals(hash_hmac('sha256', "logout\n" . $time . "\n" . $callback . "\n" . $state, $key), $signature)) {
    http_response_code(400);
    exit;
}
logoutCurrentSession();
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('Location: ' . $callback . '?' . http_build_query(['state' => $state]), true, 303);
