<?php

namespace Tests\Unit;

use App\Controllers\ApiFileController;
use App\Models\UserModel;
use App\Services\AuthenticationService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

/** Renaming and moving require the catalog permission `rename`, as WebDAV does. */
final class RenamePermissionTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string $home;

    protected function setUp(): void
    {
        parent::setUp();
        helper('auth');
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([['username' => 'erin', 'role' => 'user', 'home_dir' => '/erin', 'auth_version' => 1]]);
        $this->home = config('Storage')->fileManagerRoot . '/erin';
        mkdir($this->home, 0700, true);
        file_put_contents($this->home . '/a.txt', 'a');
    }

    protected function tearDown(): void
    {
        session()->destroy();
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testMoveRequiresRenamePermission(): void
    {
        $this->actWith(['read', 'write']);
        $this->assertSame(403, $this->move('Home/a.txt', 'Home/b.txt'));
        $this->assertFileExists($this->home . '/a.txt');

        $this->actWith(['read', 'write', 'rename']);
        $this->assertSame(200, $this->move('Home/a.txt', 'Home/b.txt'));
        $this->assertFileExists($this->home . '/b.txt');
    }

    public function testRemoteSessionsKeepRenaming(): void
    {
        (new AuthenticationService())->startRemoteSession('erin', ['mode' => 'sftp']);
        $this->assertTrue(can('rename'));
    }

    private function actWith(array $permissions): void
    {
        session()->set(['isLoggedIn' => true, 'username' => 'erin', 'auth_version' => 1,
            'connection' => ['mode' => 'local'], 'permissions' => $permissions]);
    }

    private function move(string $from, string $to): int
    {
        $request = Services::request(null, false);
        $request->setBody(json_encode(['from' => $from, 'to' => $to]));
        $controller = new ApiFileController();
        $controller->initController($request, Services::response(), Services::logger());
        return $controller->mv()->getStatusCode();
    }
}
