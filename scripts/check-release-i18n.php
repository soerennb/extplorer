<?php

declare(strict_types=1);

namespace Extplorer\Build;

/** Remove temporary files without following directory symlinks. */
function removeTree(string $path): void
{
    if (is_dir($path) && !is_link($path)) {
        foreach (new \FilesystemIterator($path) as $entry) {
            removeTree($entry->getPathname());
        }
        rmdir($path);
    } elseif (file_exists($path) || is_link($path)) {
        unlink($path);
    }
}

function readJson(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        throw new \RuntimeException("Missing or unreadable runtime translation file: {$path}");
    }

    $raw = file_get_contents($path);
    $decoded = $raw === false ? null : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new \RuntimeException("Invalid runtime translation file: {$path}");
    }

    return $decoded;
}

function runCommand(array $command, string $directory): void
{
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes, $directory);
    if (!is_resource($process)) {
        throw new \RuntimeException('Unable to start the release check command.');
    }
    fclose($pipes[0]);
    if (proc_close($process) !== 0) {
        throw new \RuntimeException('Release check command failed: ' . $command[0]);
    }
}

/** This runs in a fresh process, using only the extracted application's autoloader. */
function verifyDirectory(string $root): void
{
    define('ROOTPATH', $root . DIRECTORY_SEPARATOR);
    define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);
    require ROOTPATH . 'vendor/autoload.php';

    $manifest = readJson(FCPATH . 'assets/i18n/locales.json');
    if ($manifest === [] || !array_is_list($manifest)) {
        throw new \RuntimeException('The runtime locale manifest must be a nonempty list.');
    }

    $locales = [];
    foreach ($manifest as $entry) {
        $locale = is_array($entry) ? ($entry['code'] ?? null) : null;
        if (!is_string($locale) || !preg_match('/\A[a-z]{2,3}(?:-[a-z0-9]{2,8})?\z/', $locale)
            || in_array($locale, $locales, true)) {
            throw new \RuntimeException('The runtime locale manifest contains an invalid or duplicate locale.');
        }
        $locales[] = $locale;
    }
    if (!in_array('en', $locales, true)) {
        throw new \RuntimeException('The runtime locale manifest must include the English fallback.');
    }

    $english = readJson(FCPATH . 'assets/i18n/en.json');
    $controller = new \App\Controllers\Login();
    $method = new \ReflectionMethod($controller, 'loginTranslations');
    foreach ($locales as $locale) {
        $messages = readJson(FCPATH . 'assets/i18n/' . $locale . '.json');
        $translations = $method->invoke($controller, $locale);
        if (!is_array($translations) || !isset($translations['login_sign_in'], $translations['login_submit'])) {
            throw new \RuntimeException("{$locale}: the packaged Login controller returned no login labels.");
        }
        foreach ($translations as $key => $actual) {
            $expected = $messages[$key] ?? $english[$key] ?? null;
            if (!is_string($expected) || trim($expected) === '' || $actual !== $expected || $actual === $key) {
                throw new \RuntimeException("{$locale}: missing or incorrect packaged login translation: {$key}");
            }
        }
    }

    fwrite(STDOUT, 'release i18n: ' . count($locales) . " locales verified without translation sources.\n");
}

function verifyArchive(string $archive): void
{
    if (!is_file($archive)) {
        throw new \RuntimeException("Archive not found: {$archive}");
    }
    $root = sys_get_temp_dir() . '/extplorer-release-i18n-' . bin2hex(random_bytes(12));
    if (!mkdir($root, 0700)) {
        throw new \RuntimeException('Unable to create the temporary release directory.');
    }

    try {
        if (str_ends_with($archive, '.zip')) {
            $zip = new \ZipArchive();
            if ($zip->open($archive) !== true) {
                throw new \RuntimeException("Unable to open ZIP archive: {$archive}");
            }
            try {
                if (!$zip->extractTo($root)) {
                    throw new \RuntimeException('Unable to extract the release ZIP.');
                }
            } finally {
                $zip->close();
            }
        } elseif (str_ends_with($archive, '.tar.gz')) {
            // Use the same tar tool as build.sh; PharData cannot extract its "./" root entry.
            runCommand(['tar', '-xzf', realpath($archive), '-C', $root], $root);
        } else {
            throw new \RuntimeException('Expected a .zip or .tar.gz release archive.');
        }

        // Local builds include sources, unlike the GitHub release ZIP. Neither may depend on them.
        removeTree($root . '/resources');
        runCommand([PHP_BINARY, __FILE__, '--verify-directory', $root], $root);
    } finally {
        removeTree($root);
    }
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

try {
    if (($argv[1] ?? '') === '--verify-directory' && isset($argv[2])) {
        verifyDirectory($argv[2]);
    } elseif (count($argv) === 2) {
        verifyArchive($argv[1]);
    } else {
        throw new \InvalidArgumentException('Usage: php scripts/check-release-i18n.php <archive.zip|archive.tar.gz>');
    }
} catch (\Throwable $error) {
    fwrite(STDERR, 'release i18n: ' . $error->getMessage() . "\n");
    exit(1);
}
