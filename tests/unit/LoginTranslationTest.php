<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LoginTranslationTest extends CIUnitTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/extplorer-login-i18n-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/public/assets/i18n', 0700, true);
        copy(FCPATH . 'assets/i18n/en.json', $this->root . '/public/assets/i18n/en.json');
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

    public static function loginLabels(): array
    {
        return [
            'English' => ['en', ['Sign in', 'Username', 'Password', 'Login']],
            'German' => ['de', ['Anmelden', 'Benutzername', 'Passwort', 'Anmelden']],
            'French' => ['fr', ['Se connecter', "Nom d'utilisateur", 'Mot de passe', 'Connexion']],
            'Slovak' => ['sk', ['Prihlásiť sa', 'Používateľské meno', 'Heslo', 'Prihlásiť sa']],
        ];
    }

    #[DataProvider('loginLabels')]
    public function testRuntimeBundlesTranslateLoginWithoutSourceFiles(string $locale, array $labels): void
    {
        copy(FCPATH . 'assets/i18n/' . $locale . '.json', $this->root . '/public/assets/i18n/' . $locale . '.json');
        $this->assertDirectoryDoesNotExist($this->root . '/resources');
        $messages = $this->translations($locale);

        $this->assertSame($labels, array_map(
            static fn(string $key): string => $messages[$key],
            ['login_sign_in', 'username', 'password', 'login_submit'],
        ));
        foreach ($messages as $key => $message) {
            $this->assertNotSame($key, $message, "Untranslated {$locale} login key: {$key}");
        }
    }

    public static function invalidSelectedBundles(): array
    {
        return [
            'missing file' => [null],
            'invalid JSON' => ['{broken'],
            'JSON scalar' => ['"not a message map"'],
            'empty file' => [''],
        ];
    }

    #[DataProvider('invalidSelectedBundles')]
    public function testUnavailableSelectedBundleFallsBackToEnglish(?string $contents): void
    {
        if ($contents !== null) {
            file_put_contents($this->root . '/public/assets/i18n/de.json', $contents);
        }

        $messages = $this->translations('de');
        $this->assertSame('Sign in', $messages['login_sign_in']);
        $this->assertSame('Login', $messages['login_submit']);
    }

    public function testSelectedTranslationsOverrideEnglishWithFallbackForMissingKeys(): void
    {
        file_put_contents($this->root . '/public/assets/i18n/de.json', json_encode(['login_submit' => 'Anmelden']));
        $messages = $this->translations('de');

        $this->assertSame('Anmelden', $messages['login_submit']);
        $this->assertSame('Username', $messages['username']);
    }

    private function translations(string $locale): array
    {
        // Constants cannot be redefined in PHPUnit's bootstrapped process.
        $code = <<<'PHP'
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('ROOTPATH', $argv[1] . '/');
define('FCPATH', ROOTPATH . 'public/');
require $argv[2] . '/vendor/autoload.php';
$method = new ReflectionMethod(App\Controllers\Login::class, 'loginTranslations');
echo json_encode($method->invoke(new App\Controllers\Login(), $argv[3]), JSON_THROW_ON_ERROR);
PHP;
        $process = proc_open(
            [PHP_BINARY, '-r', $code, $this->root, rtrim(ROOTPATH, '/'), $locale],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors);

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }
}
