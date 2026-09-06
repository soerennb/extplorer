<?php

declare(strict_types=1);

function fail_config(string $message): never
{
    fwrite(STDERR, "[extplorer-init] phase=config status=failed code=config_error message={$message}\n");
    exit(1);
}

function env_value(string $key): ?string
{
    $value = getenv($key);
    return $value === false ? null : $value;
}

function read_secret(string $fileKey, string $legacyKey): ?string
{
    $file = env_value($fileKey);
    if ($file !== null && trim($file) !== '') {
        if (!is_readable(trim($file))) {
            fail_config("secret file is missing or unreadable: {$fileKey}");
        }
        $value = file_get_contents(trim($file));
        if ($value === false) {
            fail_config("secret file could not be read: {$fileKey}");
        }
        $value = rtrim($value, "\r\n");
        if ($value === '') {
            fail_config("secret file is empty: {$fileKey}");
        }
        return $value;
    }

    $value = env_value($legacyKey);
    return $value === null || $value === '' ? null : $value;
}

function parse_bool(string $key, string $value): bool
{
    $value = strtolower(trim($value));
    if (!in_array($value, ['1', 'true', 'yes', 'on', '0', 'false', 'no', 'off'], true)) {
        fail_config("{$key} must be a boolean");
    }
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function parse_int(string $key, string $value, int $minimum, int $maximum): int
{
    if (!preg_match('/\A\d+\z/', trim($value))) {
        fail_config("{$key} must be an integer");
    }
    $parsed = (int)trim($value);
    if ($parsed < $minimum || $parsed > $maximum) {
        fail_config("{$key} must be between {$minimum} and {$maximum}");
    }
    return $parsed;
}

function parse_list(string $value): array
{
    $parts = preg_split('/[\r\n,]+/', $value) ?: [];
    return array_values(array_filter(array_map('trim', $parts), static fn(string $item): bool => $item !== ''));
}

$writePath = env_value('EXTPLORER_WRITE_PATH') ?: env_value('WRITEPATH') ?: '/var/www/html/writable';
$writePath = rtrim($writePath, '/\\');
$settingsFile = $writePath . '/config/settings.php';

if (!is_dir(dirname($settingsFile)) && !mkdir(dirname($settingsFile), 0775, true) && !is_dir(dirname($settingsFile))) {
    fail_config('unable to create configuration directory');
}

$settings = [];
if (is_file($settingsFile)) {
    $content = file_get_contents($settingsFile);
    if ($content === false) {
        fail_config('unable to read settings file');
    }
    $content = preg_replace('/\A<\?php die\("Access denied"\); \?>\R?/', '', $content, 1);
    if ($content === null) {
        fail_config('unable to parse settings file');
    }
    try {
        $settings = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail_config('settings file contains invalid JSON');
    }
    if (!is_array($settings)) {
        fail_config('settings file must contain a JSON object');
    }
}

$updates = [];
$stringMap = [
    'EXTPLORER_EMAIL_PROTOCOL' => 'email_protocol',
    'EXTPLORER_SMTP_HOST' => 'smtp_host',
    'EXTPLORER_SMTP_USER' => 'smtp_user',
    'EXTPLORER_SMTP_CRYPTO' => 'smtp_crypto',
    'EXTPLORER_SENDMAIL_PATH' => 'sendmail_path',
    'EXTPLORER_EMAIL_FROM' => 'email_from',
    'EXTPLORER_EMAIL_FROM_NAME' => 'email_from_name',
];
foreach ($stringMap as $environmentKey => $settingKey) {
    $value = env_value($environmentKey);
    if ($value !== null && trim($value) !== '') {
        $updates[$settingKey] = $value;
    }
}

if (
    (env_value('EXTPLORER_SMTP_PASSWORD_FILE') !== null && trim((string)env_value('EXTPLORER_SMTP_PASSWORD_FILE')) !== '')
    || (env_value('EXTPLORER_SMTP_PASS') !== null && env_value('EXTPLORER_SMTP_PASS') !== '')
) {
    $updates['smtp_pass'] = read_secret('EXTPLORER_SMTP_PASSWORD_FILE', 'EXTPLORER_SMTP_PASS');
}
if (($value = env_value('EXTPLORER_SMTP_PORT')) !== null) {
    if (trim($value) !== '') {
        $updates['smtp_port'] = parse_int('EXTPLORER_SMTP_PORT', $value, 1, 65535);
    }
}
if (($value = env_value('EXTPLORER_DEFAULT_TRANSFER_EXPIRY')) !== null) {
    if (trim($value) !== '') {
        $updates['default_transfer_expiry'] = parse_int('EXTPLORER_DEFAULT_TRANSFER_EXPIRY', $value, 0, 3650);
    }
}
if (($value = env_value('EXTPLORER_UPLOAD_MAX_FILE_MB')) !== null) {
    if (trim($value) !== '') {
        $updates['upload_max_file_mb'] = parse_int('EXTPLORER_UPLOAD_MAX_FILE_MB', $value, 1, 10240);
    }
}
if (($value = env_value('EXTPLORER_ALLOW_PUBLIC_UPLOADS')) !== null) {
    if (trim($value) !== '') {
        $updates['allow_public_uploads'] = parse_bool('EXTPLORER_ALLOW_PUBLIC_UPLOADS', $value);
    }
}
if (($value = env_value('EXTPLORER_MOUNT_ROOT_ALLOWLIST')) !== null) {
    if (trim($value) !== '') {
        $updates['mount_root_allowlist'] = parse_list($value);
    }
}
if (($value = env_value('EXTPLORER_MOUNT_REMOTE_HOST_ALLOWLIST')) !== null) {
    if (trim($value) !== '') {
        $updates['mount_remote_host_allowlist'] = parse_list($value);
    }
}

if ($updates !== []) {
    $settings = array_merge($settings, $updates);
    try {
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail_config('unable to encode settings');
    }

    $temporary = tempnam(dirname($settingsFile), '.extplorer-settings-');
    if ($temporary === false || file_put_contents($temporary, '<?php die("Access denied"); ?>' . PHP_EOL . $json, LOCK_EX) === false) {
        fail_config('unable to write settings file');
    }
    chmod($temporary, 0640);
    if (!rename($temporary, $settingsFile)) {
        @unlink($temporary);
        fail_config('unable to activate settings file');
    }
    fwrite(STDOUT, "[extplorer-init] phase=config status=success changed=1\n");
} else {
    fwrite(STDOUT, "[extplorer-init] phase=config status=success changed=0\n");
}
