<?php

declare(strict_types=1);

// Run only in the disposable mail fixture container as its non-root PHP user.
define('FCPATH', '/app/public/');
define('ENVIRONMENT', 'production');
require '/app/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
set_exception_handler(static function (\Throwable $error): void {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
});

use App\Services\EmailService;
use App\Services\SettingsService;

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

check(posix_geteuid() !== 0, 'Mail fixture must run without root.');
$settings = ['email_protocol' => 'sendmail', 'email_from' => 'fixture@example.test',
    'email_from_name' => 'Mail fixture', 'sendmail_path' => '/tmp/untrusted-legacy-program'];
$store = new SettingsService();
$store->saveSettings($settings);
$service = new EmailService();
$result = $service->sendTestEmailWithConfig('recipient@example.test', $store->getSettings());
check($result['ok'], 'The trusted operator-owned executable with shell metacharacters in its filename must work.');
$capture = (string)getenv('EXTPLORER_MAIL_CAPTURE');
$captured = (string)file_get_contents($capture);
check(str_contains($captured, 'recipient@example.test')
    && str_contains(strtolower($captured), 'mime-version:'), 'The selected program must receive the message.');
$service->markDeliveryVerified($store->getSettings());
check($service->isDeliveryReady(), 'Only the successfully tested effective configuration becomes ready.');

$marker = config('Storage')->runtime . '/executed';
$untrusted = config('Storage')->fileManagerRoot . '/uploaded-program';
file_put_contents($untrusted, "#!/bin/sh\nprintf executed > " . escapeshellarg($marker) . "\n");
chmod($untrusted, 0500);
putenv('EXTPLORER_SENDMAIL_PATH=' . $untrusted);
check(!$service->isDeliveryReady(), 'Changing the operator path must revoke readiness.');
check(!$service->sendTestEmail('recipient@example.test'), 'Even a read-only executable owned by PHP must be denied.');
check(!$service->sendExpiryReminder(['sender_email' => 'recipient@example.test']), 'CLI reminders must enforce the same executable boundary.');
check(!file_exists($marker), 'The uploaded script must never execute.');
putenv('EXTPLORER_SENDMAIL_PATH');
check($service->deliveryStatus()['reason'] === 'sendmail_migration_required', 'A legacy path must require explicit operator migration.');
echo "Trusted Sendmail delivery, shell quoting, readiness invalidation and uploaded-program denial passed.\n";
