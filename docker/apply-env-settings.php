<?php

function env_value(string $key): ?string
{
    $value = getenv($key);
    if ($value === false) {
        return null;
    }

    return $value;
}

function env_bool(string $value): bool
{
    $value = strtolower(trim($value));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function env_list(string $value): array
{
    $parts = preg_split('/[\r\n,]+/', $value) ?: [];
    $items = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $items[] = $part;
        }
    }

    return $items;
}

$writePath = getenv('WRITEPATH') ?: '/var/www/html/writable';
$writePath = rtrim($writePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$settingsFile = $writePath . 'settings.php';

if (!is_dir($writePath)) {
    mkdir($writePath, 0775, true);
}

$defaults = [
    'email_protocol' => 'smtp',
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_user' => '',
    'smtp_pass' => '',
    'smtp_crypto' => 'tls',
    'sendmail_path' => '/usr/sbin/sendmail',
    'email_from' => 'noreply@example.com',
    'email_from_name' => 'eXtplorer',
    'default_transfer_expiry' => 7,
    'allow_public_uploads' => false,
    'mount_root_allowlist' => [],
];

$settings = $defaults;
if (file_exists($settingsFile)) {
    $content = file_get_contents($settingsFile);
    $content = preg_replace('/^<\?php die\\(\"Access denied\"\\); \\?>\\R/', '', $content ?? '');
    $decoded = json_decode($content ?? '', true);
    if (is_array($decoded)) {
        $settings = array_merge($settings, $decoded);
    }
}

$updates = [];

$envMap = [
    'EXTPLORER_EMAIL_PROTOCOL' => ['email_protocol', 'string'],
    'EXTPLORER_SMTP_HOST' => ['smtp_host', 'string'],
    'EXTPLORER_SMTP_PORT' => ['smtp_port', 'int'],
    'EXTPLORER_SMTP_USER' => ['smtp_user', 'string'],
    'EXTPLORER_SMTP_PASS' => ['smtp_pass', 'string'],
    'EXTPLORER_SMTP_CRYPTO' => ['smtp_crypto', 'string'],
    'EXTPLORER_SENDMAIL_PATH' => ['sendmail_path', 'string'],
    'EXTPLORER_EMAIL_FROM' => ['email_from', 'string'],
    'EXTPLORER_EMAIL_FROM_NAME' => ['email_from_name', 'string'],
    'EXTPLORER_DEFAULT_TRANSFER_EXPIRY' => ['default_transfer_expiry', 'int'],
    'EXTPLORER_ALLOW_PUBLIC_UPLOADS' => ['allow_public_uploads', 'bool'],
    'EXTPLORER_MOUNT_ROOT_ALLOWLIST' => ['mount_root_allowlist', 'list'],
];

foreach ($envMap as $envKey => [$settingKey, $type]) {
    $value = env_value($envKey);
    if ($value === null) {
        continue;
    }

    switch ($type) {
        case 'bool':
            $parsed = env_bool($value);
            break;
        case 'int':
            $parsed = (int) $value;
            break;
        case 'list':
            $parsed = env_list($value);
            break;
        default:
            $parsed = $value;
            break;
    }

    $updates[$settingKey] = $parsed;
}

if ($updates) {
    $settings = array_merge($settings, $updates);
    $json = json_encode($settings, JSON_PRETTY_PRINT);
    $content = '<?php die("Access denied"); ?>' . PHP_EOL . $json;
    file_put_contents($settingsFile, $content);
    echo "Applied environment settings to {$settingsFile}" . PHP_EOL;
} else {
    echo "No environment settings to apply." . PHP_EOL;
}
