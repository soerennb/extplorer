<?php

declare(strict_types=1);

// Run against each extracted release's own framework and production autoloader.
$application = realpath($argv[1] ?? '');
$phase = $argv[2] ?? '';
if ($application === false || !is_file($application . '/spark')) {
    throw new RuntimeException('Usage: upgrade-state.php <application> <seed|verify|exercise|completed>');
}
define('FCPATH', $application . '/public/');
define('ENVIRONMENT', 'production');
chdir(FCPATH);
require $application . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
helper('auth');

use App\Models\UserModel;
use App\Services\AtomicFileStore;
use App\Services\DataMigrationService;
use App\Services\DavAuthenticationService;
use App\Services\MountService;
use App\Services\ShareService;
use App\Services\TrashService;
use App\Services\UploadQuarantineService;
use App\Services\UploadSessionService;
use App\Services\VersionService;
use App\Services\VFS\LocalAdapter;
use App\Services\WebDavCredentialService;

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) throw new RuntimeException('Upgrade regression: ' . $message);
    $checks++;
}

check((new DataMigrationService())->run() === 5, 'migration schema');
$storage = config('Storage');
$files = $storage->fileManagerRoot;
$fixturePath = $storage->runtime . '/release-upgrade.json';
$users = new UserModel();
$sessions = new UploadSessionService();
$quarantine = new UploadQuarantineService();
$shares = new ShareService();
$trash = new TrashService('upgrade-admin');
$versions = new VersionService('upgrade-admin');
$mounts = new MountService();
$password = 'Upgrade-Password!2026';
$external = (string)getenv('UPGRADE_MOUNT_ROOT');

if ($phase === 'seed') {
    check($users->addUser('upgrade-admin', $password, 'admin'), 'create administrator');
    $users->saveRoles(array_merge($users->getRoles(), ['upgrade-reader' => ['read', 'download']]));
    check($users->addUser('upgrade-user', $password, 'upgrade-reader'), 'create restricted user');
    check($users->updateUser('upgrade-user', ['2fa_enabled' => true, '2fa_secret' => 'JBSWY3DPEHPK3PXP', 'recovery_codes' => ['upgrade-recovery']]), 'enable 2FA');
    $dav = (new WebDavCredentialService())->create('upgrade-user', 'Upgrade fixture');
    session()->set(['username' => 'upgrade-admin', 'permissions' => ['*']]);
    $settings = new App\Services\SettingsService();
    $settings->saveSettings(array_merge($settings->getSettings(), ['mount_root_allowlist' => [$external]]));
    file_put_contents($external . '/mounted.txt', 'external mount content');
    $mounts->addMount('upgrade-admin', 'Upgrade local', 'local', ['path' => $external]);
    file_put_contents($files . '/keep.txt', "original bytes\x00\xff\n");
    file_put_contents($files . '/history.txt', 'first revision');
    $versions->createVersion($files . '/history.txt', 'history.txt');
    file_put_contents($files . '/history.txt', 'second revision');
    file_put_contents($files . '/trash.txt', 'restore this');
    $trash->moveToTrash($files . '/trash.txt', 'trash.txt');
    $share = $shares->createShare('keep.txt', 'upgrade-admin', 'Share-Password!2026', null, 'read', ['source' => 'Home']);
    $upload = $sessions->create('upgrade-admin', 'Home', '', 'resumed.txt', 6, 65536, 1);
    file_put_contents($sessions->chunkPath($upload['id'], 0), 'abcdef');
    $sessions->storeChunk($upload['id'], 0, 6);
    $source = $quarantine->incomingPath();
    file_put_contents($source, 'scan then activate');
    $pending = $quarantine->stage($source, $files . '/scanned.txt', ['owner' => 'upgrade-admin', 'source' => 'upgrade']);
    // Seed persisted mount records through the OLD release's state writer, then
    // let its normal migration encrypt the credentials. No network is contacted.
    $remoteMounts = $mounts->getUserMounts('upgrade-admin', true);
    foreach (['ftp', 'ftps', 'sftp'] as $type) {
        $remoteMounts[$type] = ['id' => $type, 'user' => 'upgrade-user', 'name' => 'Upgrade ' . $type, 'type' => $type,
            'config' => ['host' => 'upgrade.example.invalid', 'port' => $type === 'sftp' ? 22 : 21, 'user' => 'fixture', 'pass' => 'mount-fixture-password', 'root' => '/']];
    }
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    check($key !== false && openssl_pkey_export($key, $privateKey, 'upgrade-fixture-passphrase'), 'create real encrypted SSH key');
    $remoteMounts['sftp']['config']['private_key'] = $privateKey;
    $remoteMounts['sftp']['config']['public_key'] = openssl_pkey_get_details($key)['key'];
    $remoteMounts['sftp']['config']['private_key_passphrase'] = 'upgrade-fixture-passphrase';
    AtomicFileStore::write($storage->state . '/mounts.php', $remoteMounts);
    $mounts->migrateSecrets();
    file_put_contents($fixturePath, json_encode(['upload' => $upload['id'], 'quarantine' => $pending['id'], 'share' => $share['hash'], 'dav' => $dav['secret'],
        'private_key_sha256' => hash('sha256', $privateKey), 'file_sha256' => hash_file('sha256', $files . '/keep.txt'), 'key_sha256' => hash('sha256', config('Encryption')->key)], JSON_THROW_ON_ERROR));
    chmod($fixturePath, 0600);
} elseif (!in_array($phase, ['verify', 'exercise', 'completed'], true)) {
    throw new RuntimeException('Unknown upgrade phase');
}

$fixture = json_decode(file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
check($users->verifyUser('upgrade-admin', $password) !== null, 'administrator password preserved');
check($users->verifyUser('upgrade-user', $password) !== null, 'user password preserved');
check($users->getUser('upgrade-user')['role'] === 'upgrade-reader', 'role preserved');
check(in_array('read', $users->getPermissions('upgrade-user'), true), 'permissions preserved');
check(!in_array('admin_users', $users->getPermissions('upgrade-user'), true), 'restricted user remains restricted');
check($users->getUser('upgrade-user')['2fa_enabled'] === true, '2FA enabled');
check($users->get2faSecret('upgrade-user') === 'JBSWY3DPEHPK3PXP', 'encrypted 2FA secret');
check($users->getRecoveryCodes('upgrade-user') === ['upgrade-recovery'], 'encrypted recovery codes');
check((new DavAuthenticationService())->authenticate('upgrade-user', $password) === null, 'WebDAV 2FA password bypass rejected');
check((new DavAuthenticationService())->authenticate('upgrade-user', $fixture['dav'])['username'] === 'upgrade-user', 'WebDAV app password preserved');
check(hash('sha256', config('Encryption')->key) === $fixture['key_sha256'], 'encryption key preserved');
check(hash_file('sha256', $files . '/keep.txt') === $fixture['file_sha256'], 'binary file checksum');
check((new LocalAdapter($files))->readFile('keep.txt') === file_get_contents($files . '/keep.txt'), 'local storage access');
check($shares->getShare($fixture['share'])['created_by'] === 'upgrade-admin', 'share metadata');
check($shares->verifyPassword($fixture['share'], 'Share-Password!2026'), 'share password');
$decrypted = $mounts->getUserMounts('upgrade-user', true);
foreach (['ftp', 'ftps', 'sftp'] as $type) {
    check($decrypted[$type]['config']['pass'] === 'mount-fixture-password', $type . ' encrypted mount password');
    check($decrypted[$type]['config']['host'] === 'upgrade.example.invalid', $type . ' mount configuration');
}
check(hash('sha256', $decrypted['sftp']['config']['private_key']) === $fixture['private_key_sha256'], 'encrypted SFTP private key');
check(openssl_pkey_get_private($decrypted['sftp']['config']['private_key'], $decrypted['sftp']['config']['private_key_passphrase']) !== false, 'SFTP private key remains usable');
check($decrypted['sftp']['config']['private_key_passphrase'] === 'upgrade-fixture-passphrase', 'encrypted SFTP passphrase');
check(!str_contains(file_get_contents($storage->state . '/mounts.php'), 'mount-fixture-password'), 'mount secrets remain encrypted at rest');
check(App\Services\VFS\VfsFactory::createFileSystem('upgrade-admin')->readFile('Upgrade local/mounted.txt') === 'external mount content', 'external local mount remains accessible');

if ($phase !== 'completed') {
    check(count($trash->listItems()) === 1, 'trash metadata');
    check(count($versions->listVersions('history.txt')) === 1, 'version metadata');
    check($sessions->get($fixture['upload'])['owner'] === 'upgrade-admin', 'pending upload ownership');
    check($sessions->missingChunks($sessions->get($fixture['upload'])) === [], 'stored upload chunk');
    check($quarantine->get($fixture['quarantine'])['status'] === 'pending', 'quarantine pending');
}
if ($phase === 'exercise') {
    check($sessions->assemble($fixture['upload'], $files . '/resumed.txt') === 6, 'complete old upload');
    $quarantine->markResult($fixture['quarantine'], 'clean', 'upgrade fixture scan');
    $trash->restore($trash->listItems()[0]['id'], new LocalAdapter($files));
    $versions->restoreVersion('history.txt', $versions->listVersions('history.txt')[0]['id'], new LocalAdapter($files));
}
if ($phase === 'exercise' || $phase === 'completed') {
    check(file_get_contents($files . '/resumed.txt') === 'abcdef', 'resumed upload payload');
    check(file_get_contents($files . '/scanned.txt') === 'scan then activate', 'old quarantine activation');
    check(file_get_contents($files . '/trash.txt') === 'restore this', 'old trash restore');
    check(file_get_contents($files . '/history.txt') === 'first revision', 'old version restore');
    check($quarantine->get($fixture['quarantine'])['status'] === 'clean', 'quarantine final state');
    check($trash->listItems() === [], 'trash restore consumes metadata');
}
echo json_encode(['application_version' => config('App')->version, 'phase' => $phase, 'checks' => $checks, 'result' => 'passed'], JSON_THROW_ON_ERROR) . "\n";
