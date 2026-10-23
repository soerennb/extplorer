<?php

namespace Tests\Unit;

use App\Controllers\ApiDownloadController;
use App\Controllers\ApiFileController;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

/**
 * Per-account extension policies apply to every operation that introduces a
 * filename, not only to uploads.
 */
final class AccountFilePolicyTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string $home;

    protected function setUp(): void
    {
        parent::setUp();
        helper('auth');
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([[
            'username' => 'carol', 'role' => 'user', 'home_dir' => '/carol', 'auth_version' => 1,
            'allowed_extensions' => 'jpg', 'blocked_extensions' => '',
        ]]);
        $this->home = config('Storage')->fileManagerRoot . '/carol';
        mkdir($this->home . '/folder', 0700, true);
        file_put_contents($this->home . '/photo.jpg', 'jpeg');
        session()->set(['isLoggedIn' => true, 'username' => 'carol', 'auth_version' => 1,
            'connection' => ['mode' => 'local'], 'allowed_extensions' => 'jpg', 'blocked_extensions' => '',
            'permissions' => ['read', 'write', 'rename', 'archive', 'extract']]);
    }

    protected function tearDown(): void
    {
        session()->destroy();
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testSaveCannotCreateDisallowedFileType(): void
    {
        $response = $this->files(['path' => 'Home/page.html', 'content' => '<h1>x</h1>'])->save();

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertFileDoesNotExist($this->home . '/page.html');
        $this->assertSame(200, $this->files(['path' => 'Home/new.jpg', 'content' => 'jpeg'])->save()->getStatusCode());
    }

    public function testMoveAndCopyCannotRenameIntoDisallowedFileType(): void
    {
        $this->assertGreaterThanOrEqual(400, $this->files(['from' => 'Home/photo.jpg', 'to' => 'Home/photo.html'])->mv()->getStatusCode());
        $this->assertGreaterThanOrEqual(400, $this->files(['from' => 'Home/photo.jpg', 'to' => 'Home/copy.svg'])->cp()->getStatusCode());
        $this->assertFileExists($this->home . '/photo.jpg');
        $this->assertFileDoesNotExist($this->home . '/photo.html');
        $this->assertFileDoesNotExist($this->home . '/copy.svg');

        // Directories carry no file type and remain manageable.
        $this->assertSame(200, $this->files(['from' => 'Home/folder', 'to' => 'Home/renamed.dir'])->mv()->getStatusCode());
    }

    public function testArchiveCannotCreateDisallowedFileType(): void
    {
        $response = $this->downloads(['paths' => ['Home/photo.jpg'], 'name' => 'bundle.zip', 'cwd' => 'Home'])->archive();

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertFileDoesNotExist($this->home . '/bundle.zip');
    }

    public function testExtractCannotIntroduceDisallowedFileTypes(): void
    {
        // An administrator placed the archive; carol may extract its allowed content only.
        $zip = new \ZipArchive();
        $zip->open($this->home . '/import.zip', \ZipArchive::CREATE);
        $zip->addFromString('ok.jpg', 'jpeg');
        $zip->addFromString('nested/evil.html', '<script>alert(1)</script>');
        $zip->close();

        $response = $this->downloads(['path' => 'Home/import.zip', 'cwd' => 'Home/folder'])->extract();

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertFileDoesNotExist($this->home . '/folder/nested/evil.html');
        $this->assertFileDoesNotExist($this->home . '/folder/ok.jpg');
    }

    private function files(array $body): ApiFileController
    {
        $controller = new ApiFileController();
        $controller->initController($this->request($body), Services::response(), Services::logger());
        return $controller;
    }

    private function downloads(array $body): ApiDownloadController
    {
        $controller = new ApiDownloadController();
        $controller->initController($this->request($body), Services::response(), Services::logger());
        return $controller;
    }

    private function request(array $body)
    {
        $request = Services::request(null, false);
        $request->setBody(json_encode($body));
        return $request;
    }
}
