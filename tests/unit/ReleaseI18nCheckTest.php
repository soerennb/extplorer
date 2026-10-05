<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ReleaseI18nCheckTest extends CIUnitTestCase
{
    private string $root;
    private array $files;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/extplorer-archive-i18n-test-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0700);
        $this->files = [];
        // Bundle the actual controllers with their minimal framework dependency.
        foreach (['app/Controllers/Login.php', 'app/Controllers/BaseController.php',
            'vendor/codeigniter4/framework/system/Controller.php'] as $path) {
            $this->files[$path] = file_get_contents(ROOTPATH . $path);
        }
        foreach (['locales', 'en', 'de', 'fr', 'sk'] as $locale) {
            $path = 'public/assets/i18n/' . $locale . '.json';
            $this->files[$path] = file_get_contents(ROOTPATH . $path);
        }
        $this->files['vendor/autoload.php'] = <<<'PHP'
<?php
require __DIR__ . '/codeigniter4/framework/system/Controller.php';
require __DIR__ . '/../app/Controllers/BaseController.php';
require __DIR__ . '/../app/Controllers/Login.php';
PHP;
        $this->files['resources/i18n/en/01-common.json'] = '{"login_submit":"Source must not be needed"}';
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($this->root);
        parent::tearDown();
    }

    public static function archiveFormats(): array
    {
        return ['ZIP' => ['zip'], 'TAR.GZ' => ['tar.gz']];
    }

    #[DataProvider('archiveFormats')]
    public function testPackagedLoginWorksForBothArchiveFormats(string $format): void
    {
        [$status, $output] = $this->check($this->archive($format));
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('4 locales verified without translation sources', $output);
    }

    public function testMissingRuntimeBundleFailsEvenWhenSourcesArePackaged(): void
    {
        unset($this->files['public/assets/i18n/en.json']);
        [$status, $output] = $this->check($this->archive('zip'));
        $this->assertSame(1, $status, $output);
        $this->assertStringContainsString('Missing or unreadable runtime translation file', $output);
    }

    public function testUntranslatedLoginKeyFailsTheArchiveCheck(): void
    {
        $messages = json_decode($this->files['public/assets/i18n/de.json'], true);
        $messages['login_submit'] = 'login_submit';
        $this->files['public/assets/i18n/de.json'] = json_encode($messages);
        [$status, $output] = $this->check($this->archive('zip'));
        $this->assertSame(1, $status, $output);
        $this->assertStringContainsString('de: missing or incorrect packaged login translation: login_submit', $output);
    }

    private function archive(string $format): string
    {
        $path = $this->root . '/release.' . $format;
        if ($format === 'zip') {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
            foreach ($this->files as $name => $contents) {
                $zip->addFromString($name, $contents);
            }
            $zip->close();
        } else {
            $stage = $this->root . '/stage';
            foreach ($this->files as $name => $contents) {
                $destination = $stage . '/' . $name;
                if (!is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0700, true);
                }
                file_put_contents($destination, $contents);
            }
            // Match build.sh, including the "./" root and entry prefixes.
            [$status, $output] = $this->runProcess(['tar', '-czf', $path, '-C', $stage, '.']);
            $this->assertSame(0, $status, $output);
        }
        return $path;
    }

    private function check(string $archive): array
    {
        return $this->runProcess([PHP_BINARY, ROOTPATH . 'scripts/check-release-i18n.php', $archive]);
    }

    private function runProcess(array $command): array
    {
        $process = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $output];
    }
}
