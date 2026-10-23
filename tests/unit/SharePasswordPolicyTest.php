<?php

namespace Tests\Unit;

use App\Controllers\ApiShareController;
use App\Controllers\ShareController;
use App\Models\UserModel;
use App\Services\ShareService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

/** Share passwords protect public links against online guessing. */
final class SharePasswordPolicyTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        helper('auth');
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([['username' => 'pia', 'role' => 'user', 'home_dir' => '/pia', 'auth_version' => 1]]);
        mkdir(config('Storage')->fileManagerRoot . '/pia/report', 0700, true);
        session()->set(['isLoggedIn' => true, 'username' => 'pia', 'permissions' => ['read'], 'auth_version' => 1,
            'connection' => ['mode' => 'local']]);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        session()->destroy();
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testWeakOrMalformedSharePasswordsAreRejected(): void
    {
        foreach (['a', '       ', '1234567', ['secret-password'], 12345678] as $password) {
            $this->assertSame(422, $this->create(['path' => 'Home/report', 'password' => $password]), json_encode($password));
        }
        $this->assertSame([], (new ShareService())->listUserShares('pia'));
        $this->assertSame(200, $this->create(['path' => 'Home/report', 'password' => 'correct horse']));
        $this->assertSame(200, $this->create(['path' => 'Home/report']));
    }

    public function testMalformedPasswordSubmissionIsAnOrdinaryFailure(): void
    {
        $hash = (new ShareService())->createShare('Home/report', 'pia', 'correct horse')['hash'];
        $_POST = ['password' => ['correct horse']];
        $request = Services::request(null, false);
        $request->setGlobal('post', $_POST);
        $controller = new ShareController();
        $controller->initController($request, Services::response(null, false), Services::logger());

        $response = $controller->auth($hash);

        $this->assertLessThan(500, $response->getStatusCode());
        $this->assertNull(session('share_verified_' . $hash));
    }

    private function create(array $body): int
    {
        $request = Services::request(null, false);
        $request->setBody(json_encode($body));
        $controller = new ApiShareController();
        $controller->initController($request, Services::response(null, false), Services::logger());
        return $controller->shareCreate()->getStatusCode();
    }
}
