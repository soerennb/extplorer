<?php

namespace Tests\Unit;

use App\Controllers\ShareController;
use App\Models\UserModel;
use App\Services\ShareService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\StorageSandbox;

/**
 * Public shares reveal only what they list: upload-mode shares are blind
 * drop boxes, and hidden entries of read shares are never served.
 */
final class ShareVisibilityTest extends CIUnitTestCase
{
    private StorageSandbox $sandbox;
    private string $box;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = new StorageSandbox();
        (new UserModel())->saveUsers([['username' => 'olga', 'role' => 'user', 'home_dir' => '/olga', 'auth_version' => 1]]);
        $this->box = config('Storage')->fileManagerRoot . '/olga/box';
        mkdir($this->box . '/.git', 0700, true);
        mkdir($this->box . '/docs', 0700, true);
        file_put_contents($this->box . '/visible.txt', 'visible');
        file_put_contents($this->box . '/.env', 'SECRET=1');
        file_put_contents($this->box . '/.git/config', 'token');
        file_put_contents($this->box . '/docs/.notes', 'private');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        session()->destroy();
        $this->sandbox->close();
        parent::tearDown();
    }

    public function testUploadShareDoesNotListExistingFiles(): void
    {
        $hash = (new ShareService())->createShare('Home/box', 'olga', null, null, 'upload')['hash'];

        $payload = json_decode($this->controller([])->ls($hash)->getBody(), true);

        $this->assertSame([], $payload['items']);
        $this->assertIsArray($payload['upload_policy']);
        $this->assertSame([], json_decode($this->controller(['path' => 'docs'])->ls($hash)->getBody(), true)['items']);
    }

    public function testReadShareDoesNotServeHiddenEntries(): void
    {
        $hash = (new ShareService())->createShare('Home/box', 'olga')['hash'];

        $this->assertSame(200, $this->controller(['path' => 'visible.txt'])->download($hash)->getStatusCode());
        foreach (['.env', '.git/config', 'docs/.notes', './.env'] as $path) {
            $this->assertGreaterThanOrEqual(400, $this->controller(['path' => $path])->download($hash)->getStatusCode(), $path);
        }
        $this->assertGreaterThanOrEqual(400, $this->controller(['path' => '.git'])->ls($hash)->getStatusCode());
        $names = array_column(json_decode($this->controller(['path' => 'docs'])->ls($hash)->getBody(), true)['items'], 'name');
        $this->assertNotContains('.notes', $names);
    }

    public function testFolderArchiveOmitsHiddenEntries(): void
    {
        $zipPath = config('Storage')->cache . '/visibility-test.zip';
        $method = (new \ReflectionClass(ShareController::class))->getMethod('zipDirectory');
        $method->invoke(new ShareController(), $this->box, $zipPath);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) $names[] = $zip->getNameIndex($i);
        $zip->close();

        $this->assertContains('visible.txt', $names);
        foreach ($names as $name) {
            $this->assertDoesNotMatchRegularExpression('#(^|/)\.#', $name);
        }
    }

    private function controller(array $query): ShareController
    {
        $_GET = $query;
        $request = Services::request(null, false);
        $request->setGlobal('get', $query);
        $controller = new ShareController();
        $controller->initController($request, Services::response(null, false), Services::logger());
        return $controller;
    }
}
