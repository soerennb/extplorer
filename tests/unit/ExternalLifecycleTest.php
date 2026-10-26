<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\UserModel;
use App\Services\AuthenticationService;
use App\Services\DavAuthenticationService;
use App\Services\ExternalAuth\ExternalAccountService;
use App\Services\ExternalAuth\ExternalAuthConfiguration;
use App\Services\ExternalAuth\ExternalLifecycleService;
use App\Services\ExternalAuth\ExternalIdentity;
use App\Services\ExternalAuth\ExternalSessionPolicy;
use App\Services\ExternalAuth\ExternalSessionRegistry;
use App\Services\ExternalAuth\ExternalState;
use App\Services\ExternalAuth\ProviderSession;
use App\Services\ExternalAuth\SignedExternalEvents;
use App\Services\WebDavCredentialService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\StorageSandbox;

final class ExternalLifecycleTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string $configurationFile;
    private string $secretFile;
    private string|false $previous;
    private array $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        session()->destroy();
        $this->configurationFile = tempnam(sys_get_temp_dir(), 'extplorer-lifecycle-');
        $this->secretFile = tempnam(sys_get_temp_dir(), 'extplorer-events-');
        file_put_contents($this->secretFile, str_repeat('a', 64));
        $this->settings = ['extension_path' => ROOTPATH . 'tests/Support/ExternalAuth', 'providers' => ['website' => [
            'type' => 'custom', 'class' => 'FixtureProvider', 'issuer' => 'https://website.test',
            'base_role' => 'user', 'group_map' => ['member' => []], 'webdav_enabled' => true,
            'events_secret_file' => $this->secretFile,
        ]]];
        $this->previous = getenv('EXTPLORER_AUTH_CONFIG_FILE');
        putenv('EXTPLORER_AUTH_CONFIG_FILE=' . $this->configurationFile);
        $this->saveConfiguration();
    }

    protected function tearDown(): void
    {
        session()->destroy();
        unlink($this->configurationFile);
        unlink($this->secretFile);
        putenv($this->previous === false ? 'EXTPLORER_AUTH_CONFIG_FILE' : 'EXTPLORER_AUTH_CONFIG_FILE=' . $this->previous);
        $this->sandbox->close();
        parent::tearDown();
    }

    private function saveConfiguration(): void
    {
        file_put_contents($this->configurationFile, json_encode($this->settings));
    }

    private function identity(): ExternalIdentity { return new ExternalIdentity('https://website.test', 'alice-id', 'Alice', ['member'], time()); }

    private function account(): array
    {
        return (new ExternalAccountService())->synchronize('website', (new ExternalAuthConfiguration())->provider('website'), $this->identity());
    }

    private function login(string $sid = 'sid-a'): array
    {
        $user = $this->account();
        $configuration = new ExternalAuthConfiguration();
        $this->assertTrue((new AuthenticationService())->startExternalSession($user, 'website', $configuration->fingerprint('website'), 3600, new ProviderSession($sid)));
        return $user;
    }

    private function update(int $revision, string $status = 'suspended', array $groups = ['member']): void
    {
        (new ExternalLifecycleService())->accountUpdated('website', (new ExternalAuthConfiguration())->provider('website'),
            ['id' => 'event-' . $revision, 'revision' => $revision, 'sub' => 'alice-id', 'status' => $status, 'groups' => $groups]);
    }

    public static function backends(): iterable { yield ['file']; yield ['sqlite']; }

    #[DataProvider('backends')]
    public function testSidLogoutAffectsOnlyItsSessionsAndPreservesAppPasswords(string $driver): void
    {
        $this->sandbox->close();
        $this->sandbox = new StorageSandbox($driver);
        $user = $this->login('sid-a');
        $first = session('external_auth');
        $credential = (new WebDavCredentialService())->create($user['username'], 'laptop');
        $this->login('sid-b');
        $second = session('external_auth');
        $lifecycle = new ExternalLifecycleService();
        $lifecycle->sessionsRevoked('website', 'https://website.test', 'alice-id', 'sid-a', 'logout-1', time() + 300);
        $lifecycle->sessionsRevoked('website', 'https://website.test', 'alice-id', 'sid-a', 'logout-1', time() + 300);
        session()->set('external_auth', $first);
        $this->assertFalse(ExternalSessionPolicy::isValid($user));
        session()->set('external_auth', $second);
        $this->assertTrue(ExternalSessionPolicy::isValid($user));
        $this->assertNotNull((new DavAuthenticationService())->authenticate($user['username'], $credential['secret']));
        $lifecycle->sessionsRevoked('website', 'https://website.test', 'alice-id', null, 'logout-2', time() + 300);
        $this->assertFalse(ExternalSessionPolicy::isValid($user));
        $this->assertTrue(ExternalState::accountAllowed($user));
    }

    #[DataProvider('backends')]
    public function testAccountSuspensionSurvivesDeletionAndReactivationNeverRestoresOldCredentials(string $driver): void
    {
        $this->sandbox->close();
        $this->sandbox = new StorageSandbox($driver);
        $user = $this->login();
        $credential = (new WebDavCredentialService())->create($user['username'], 'device');
        $this->update(2);
        $this->assertFalse(ExternalState::accountAllowed($user));
        $this->assertFalse(ExternalSessionPolicy::isValid($user));
        $this->assertNull((new DavAuthenticationService())->authenticate($user['username'], $credential['secret']));
        $this->update(1, 'active');
        $this->assertFalse(ExternalState::accountAllowed($user));
        (new UserModel())->saveUsers([]);
        try { $this->account(); $this->fail('Deleting the account must not erase the identity suspension.'); }
        catch (\RuntimeException) {}
        $this->update(3, 'active');
        $newUser = $this->login();
        $this->assertTrue(ExternalState::accountAllowed($newUser));
        $this->assertNull((new DavAuthenticationService())->authenticate($newUser['username'], $credential['secret']));
    }

    #[DataProvider('backends')]
    public function testConcurrentReplicasKeepTheNewestAccountSnapshot(string $driver): void
    {
        if (!function_exists('pcntl_fork')) $this->markTestSkipped('Process concurrency requires pcntl.');
        $this->sandbox->close();
        $this->sandbox = new StorageSandbox($driver);
        $configuration = config('Database')->tests;
        if ($configuration['DBDriver'] === 'SQLite3') {
            $configuration['database'] = $this->sandbox->root . '/lifecycle.sqlite';
            $configuration['busyTimeout'] = 10000;
        }
        $configuration['DBPrefix'] = 'lifecycle_' . bin2hex(random_bytes(4)) . '_';
        $connect = static function () use ($driver, $configuration): void {
            \App\Services\AtomicFileStore::reset();
            if ($driver !== 'sqlite') return;
            $backend = new \App\Services\State\DatabaseStateBackend(db_connect($configuration, false));
            (new \ReflectionProperty(\App\Services\AtomicFileStore::class, 'backend'))->setValue(null, $backend);
            (new \ReflectionProperty(\App\Services\AtomicFileStore::class, 'backendDriver'))->setValue(null, $driver);
        };
        $connect();
        $user = $this->login();
        $children = [];
        foreach ([2, 5, 1, 4, 3] as $revision) {
            $pid = pcntl_fork();
            if ($pid === -1) $this->fail('Cannot fork lifecycle worker.');
            if ($pid === 0) {
                try {
                    $connect();
                    $this->update($revision, $revision === 5 ? 'suspended' : 'active');
                    exit(0);
                } catch (\Throwable) { exit(1); }
            }
            $children[] = $pid;
        }
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }
        $connect();
        $state = ExternalState::snapshot();
        $this->assertSame(5, $state['identities'][ExternalState::identityKey($user['external_identity'])]['revision']);
        $this->assertFalse(ExternalState::accountAllowed($user));
        $this->assertFalse(ExternalSessionPolicy::isValid((new UserModel())->getUser($user['username'])));
    }

    public function testProviderRemovalRecordedAtReadinessPermanentlyRevokesCredentials(): void
    {
        $user = $this->login();
        $credential = (new WebDavCredentialService())->create($user['username'], 'device');
        $configured = $this->settings;
        $this->settings['providers'] = [];
        $this->saveConfiguration();
        (new ExternalAuthConfiguration())->assertReady();
        $this->settings = $configured;
        $this->saveConfiguration();
        $this->assertFalse((new WebDavCredentialService())->verify($user['username'], $credential['secret']));
    }

    public function testExternalSharesPauseDuringSuspensionAndResumeUnderCurrentRights(): void
    {
        $user = $this->login();
        mkdir(config('Storage')->fileManagerRoot . $user['home_dir'], 0700, true);
        mkdir(config('Storage')->uploads . '/shares/0123456789abcdef', 0700, true);
        $service = new \App\Services\ShareService();
        $shares = [
            ['path' => 'Home/report.txt', 'created_by' => $user['username']],
            ['path' => '0123456789abcdef', 'source' => 'transfer', 'created_by' => $user['username']],
        ];
        foreach ($shares as $share) $this->assertCount(2, $service->resolveSharePaths($share));
        $this->update(1);
        foreach ($shares as $share) {
            try { $service->resolveSharePaths($share); $this->fail('Suspended owner share must fail.'); }
            catch (\RuntimeException $error) { $this->assertStringContainsString('not active', $error->getMessage()); }
        }
        $this->update(2, 'active');
        foreach ($shares as $share) $this->assertCount(2, $service->resolveSharePaths($share));
    }

    public function testExternalReactivationNeverRemovesALocalAdministratorBlock(): void
    {
        $user = $this->login();
        (new UserModel())->updateUser($user['username'], ['disabled' => true]);
        $this->update(1);
        $this->update(2, 'active');
        $this->assertTrue((new UserModel())->getUser($user['username'])['disabled']);
        $this->expectException(\RuntimeException::class);
        $this->account();
    }

    public function testAnInFlightLoginCannotOverwriteNewerRightsOrSessionRevocation(): void
    {
        $user = $this->account();
        $cursor = ExternalState::snapshot()['sequence'];
        $this->update(1, 'active', []);
        $this->assertFalse(ExternalState::accountAllowed($user));
        try {
            (new ExternalAccountService())->synchronize('website', (new ExternalAuthConfiguration())->provider('website'), $this->identity(), $cursor);
            $this->fail('An old proof must not overwrite newer events.');
        } catch (\RuntimeException) {}
        $this->update(2, 'active');
        $user = $this->account();
        $cursor = ExternalState::snapshot()['sequence'];
        (new ExternalLifecycleService())->sessionsRevoked('website', 'https://website.test', null, 'pending-sid', 'logout-pending', time() + 300);
        $this->expectException(\RuntimeException::class);
        (new ExternalSessionRegistry())->register($user, (new ExternalAuthConfiguration())->fingerprint('website'), 3600, new ProviderSession('pending-sid'), $cursor);
    }

    public function testARejectedGroupSnapshotRevokesExistingAccessInsteadOfLeavingItStale(): void
    {
        $user = $this->login();
        $rejected = new ExternalIdentity('https://website.test', 'alice-id', 'Alice', ['not-admitted']);
        try {
            (new ExternalAccountService())->synchronize('website', (new ExternalAuthConfiguration())->provider('website'), $rejected);
            $this->fail('No admitted group.');
        } catch (\RuntimeException) {}
        $this->assertFalse(ExternalState::accountAllowed($user));
        $this->assertFalse(ExternalSessionPolicy::isValid($user));
    }

    public function testEventsAreSignedAudienceBoundRevisionOrderedAndReplaySafe(): void
    {
        $user = $this->login();
        $event = ['provider' => 'website', 'iss' => 'https://website.test', 'aud' => site_url('auth/events/website'),
            'iat' => time(), 'id' => 'signed-1', 'type' => 'account.updated', 'sub' => 'alice-id',
            'revision' => 1, 'status' => 'suspended', 'groups' => ['member']];
        $receiver = new SignedExternalEvents();
        $send = static function (array $event) use ($receiver): void {
            $body = json_encode($event);
            $receiver->receive('website', $body, (string)$event['iat'], 'v1=' . hash_hmac('sha256', $event['iat'] . '.' . $body, str_repeat('a', 64)));
        };
        $send($event);
        $this->assertFalse(ExternalState::accountAllowed($user));
        // Reusing an ID with a larger revision still cannot apply another mutation.
        $event['revision'] = 2;
        $event['status'] = 'active';
        $send($event);
        $this->assertFalse(ExternalState::accountAllowed($user));
        $event['id'] = 'signed-2';
        $send($event);
        $this->assertTrue(ExternalState::accountAllowed($user));
        $event['aud'] = 'https://another-installation.test/auth/events/website';
        $this->expectException(\RuntimeException::class);
        $send($event);
    }

    public function testExpiredChangedOrBlockedAppPasswordsNeverResurrect(): void
    {
        $user = $this->login();
        $service = new WebDavCredentialService();
        $credential = $service->create($user['username'], 'device');
        $this->assertGreaterThan(time() + 2591900, $credential['credential']['expires_at']);
        $this->assertTrue($service->verify($user['username'], $credential['secret']));
        $external = session('external_auth');
        $cursor = ExternalState::snapshot()['sequence'];
        $this->settings['providers']['website']['webdav_enabled'] = false;
        $this->saveConfiguration();
        $this->assertFalse($service->verify($user['username'], $credential['secret']));
        $this->settings['providers']['website']['webdav_enabled'] = true;
        $this->saveConfiguration();
        $this->assertFalse($service->verify($user['username'], $credential['secret']));
        session()->set('external_auth', $external);
        $this->assertFalse(ExternalSessionPolicy::isValid($user));
        try {
            (new ExternalAccountService())->synchronize('website', (new ExternalAuthConfiguration())->provider('website'), $this->identity(), $cursor);
            $this->fail('A provider change must reject pending logins even after restoring the original configuration.');
        } catch (\RuntimeException) {}
        $this->login();
        $credential = $service->create($user['username'], 'expired');
        ExternalState::transaction(static function (array &$documents): void {
            foreach ($documents['webdav_credentials'] as &$entry) $entry['expires_at'] = time() - 1;
        });
        $this->assertFalse($service->verify($user['username'], $credential['secret']));
    }

    public function testProviderHintsAreEncryptedAndLogoutReturnIsBrowserBoundAndSingleUse(): void
    {
        $user = $this->account();
        $id = (new ExternalSessionRegistry())->register($user, 'fingerprint', 3600, new ProviderSession('sid', 'private-id-token'));
        $hint = ExternalState::snapshot()['sessions'][$id]['id_token_hint'];
        $this->assertNotSame('private-id-token', $hint);
        $this->assertSame('private-id-token', (new \App\Services\RemoteCredentialService())->reveal($hint));
        $state = bin2hex(random_bytes(32));
        $cookie = bin2hex(random_bytes(32));
        ExternalState::transaction(static function (array &$documents) use ($state, $cookie): void {
            $documents['external_auth']['logouts'][hash('sha256', $state)] = ['cookie_hash' => hash('sha256', $cookie), 'expires_at' => time() + 60];
        });
        $logout = new \App\Services\ExternalAuth\ExternalLogoutService();
        $this->assertFalse($logout->complete($state, bin2hex(random_bytes(32))));
        $this->assertTrue($logout->complete($state, $cookie));
        $this->assertFalse($logout->complete($state, $cookie));
    }

    public function testCleanupKeepsPermanentIdentityLocksAndRejectsPreUpgradeSessions(): void
    {
        $user = $this->login();
        $external = session('external_auth');
        unset($external['registration']);
        session()->set('external_auth', $external);
        $this->assertFalse(ExternalSessionPolicy::isValid($user));
        $this->update(1);
        ExternalState::transaction(static function (array &$documents): void {
            foreach ($documents['external_auth']['sessions'] as &$entry) $entry['expires_at'] = time() - 1;
            foreach ($documents['external_auth']['replays'] as &$entry) $entry['expires_at'] = time() - 1;
        });
        $stats = ExternalState::cleanup();
        $this->assertSame(1, $stats['sessions']);
        $this->assertFalse(ExternalState::accountAllowed($user));
    }
}
