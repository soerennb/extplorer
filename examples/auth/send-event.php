<?php

declare(strict_types=1);

// Run server-side only. Call from a durable website/IdP event outbox and retry failed
// requests with the SAME id/revision. Load the account snapshot from your database.
if (PHP_SAPI !== 'cli' || $argc !== 3) exit("Usage: php send-event.php event.json events-key-file\n");
$event = json_decode((string)file_get_contents($argv[1]), true, 16, JSON_THROW_ON_ERROR);
$key = rtrim((string)file_get_contents($argv[2]), "\r\n");
if (strlen($key) < 32 || !str_starts_with($event['aud'] ?? '', 'https://')) throw new RuntimeException('Invalid event target/key.');
$event['iat'] = time();
$body = json_encode($event, JSON_THROW_ON_ERROR);
$handle = curl_init($event['aud']);
curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Extplorer-Event-Time: ' . $event['iat'],
        'X-Extplorer-Event-Signature: v1=' . hash_hmac('sha256', $event['iat'] . '.' . $body, $key)]]);
$result = curl_exec($handle);
$status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
if ($result === false || $status !== 200) throw new RuntimeException('External event delivery failed; retry from the outbox.');
echo "Event accepted.\n";
