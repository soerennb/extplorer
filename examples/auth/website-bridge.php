<?php

declare(strict_types=1);

/* Integration template: place on YOUR website, outside eXtplorer's code/data tree.
 * site-bootstrap.php must provide requireSignedInUser() and requireFreshLogin().
 * They must use your site's real authentication and CSRF protections.
 */
require __DIR__ . '/site-bootstrap.php';

$callback = 'https://files.example.com/login/external/website/callback';
$issuer = 'https://www.example.com';
$key = rtrim((string)file_get_contents('/run/secrets/website_signing_key'), "\r\n");
if (strlen($key) < 32 || ($_GET['callback'] ?? null) !== $callback
    || !is_string($_GET['state'] ?? null) || !preg_match('/\A[a-f0-9]{64}\z/', $_GET['state'])
    || !is_string($_GET['nonce'] ?? null) || !preg_match('/\A[a-f0-9]{64}\z/', $_GET['nonce'])) {
    http_response_code(400);
    exit;
}
$user = requireSignedInUser(); // stable id, display name, server-derived groups, auth_time
if (($_GET['reauthenticate'] ?? '') === '1') {
    $user = requireFreshLogin(); // must actually challenge the user, not just refresh auth_time
}
$now = time();
$claims = [
    'iss' => $issuer, 'sub' => $user['id'], 'name' => $user['name'], 'groups' => $user['groups'],
    'auth_time' => $user['auth_time'], 'iat' => $now, 'exp' => $now + 60,
    'aud' => $callback, 'state' => $_GET['state'], 'nonce' => $_GET['nonce'],
];
$payload = rtrim(strtr(base64_encode(json_encode($claims, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
$ticket = $payload . '.' . hash_hmac('sha256', $payload, $key);
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('Location: ' . $callback . '?' . http_build_query(['state' => $_GET['state'], 'ticket' => $ticket]), true, 303);
