<?php

namespace App\Services;

use App\Services\State\StateBackendFactory;
use App\Services\State\StateBackendInterface;
use JsonException;
use RuntimeException;

/**
 * Reads and writes the PHP-wrapped JSON files used for persistent state.
 */
final class AtomicFileStore
{
    private const HEADER = '<?php die("Access denied"); ?>';

    private static ?StateBackendInterface $backend = null;
    private static ?string $backendDriver = null;

    public static function read(string $path, array $default = []): array
    {
        $backend = self::backend($path);
        if ($backend !== null) {
            return $backend->read($path, $default);
        }

        if (!is_file($path)) {
            return $default;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Unable to read persistent state file: {$path}");
        }

        $content = preg_replace(
            '/\A<\?php die\("Access denied"\); \?>\R?/',
            '',
            $content,
            1,
            $count
        );
        if ($content === null || ($count === 0 && str_starts_with($content, '<?php'))) {
            throw new RuntimeException("Invalid protected state file: {$path}");
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "Invalid JSON in persistent state file: {$path}",
                0,
                $exception
            );
        }

        if (!is_array($decoded)) {
            throw new RuntimeException("Persistent state must contain a JSON object or array: {$path}");
        }

        return $decoded;
    }

    public static function readLegacyJson(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Unable to read legacy state file: {$path}");
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "Invalid JSON in legacy state file: {$path}",
                0,
                $exception
            );
        }

        if (!is_array($decoded)) {
            throw new RuntimeException("Legacy state must contain a JSON object or array: {$path}");
        }

        return $decoded;
    }

    public static function write(string $path, array $data): void
    {
        $backend = self::backend($path);
        if ($backend !== null) {
            $backend->write($path, $data);
            return;
        }

        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create state directory: {$directory}");
        }

        try {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Unable to encode persistent state: {$path}", 0, $exception);
        }

        $temporary = tempnam($directory, '.extplorer-state-');
        if ($temporary === false) {
            throw new RuntimeException("Unable to create temporary state file: {$path}");
        }

        try {
            $written = file_put_contents($temporary, self::HEADER . PHP_EOL . $json, LOCK_EX);
            if ($written === false) {
                throw new RuntimeException("Unable to write persistent state: {$path}");
            }
            chmod($temporary, 0640);
            if (!rename($temporary, $path)) {
                throw new RuntimeException("Unable to activate persistent state: {$path}");
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * Execute a read/modify/write operation while holding a sidecar lock.
     *
     * Atomic replacement alone prevents torn reads, but it cannot prevent two
     * writers from both reading the same old state and losing one another's
     * changes. The callback may mutate the data by reference and its return
     * value is passed back to the caller.
     */
    public static function transaction(string $path, callable $callback, array $default = []): mixed
    {
        $backend = self::backend($path);
        if ($backend !== null) {
            return $backend->transaction($path, $callback, $default);
        }

        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create state directory: {$directory}");
        }

        $lockPath = $path . '.lock';
        $lock = fopen($lockPath, 'c');
        if ($lock === false) {
            throw new RuntimeException("Unable to open state lock: {$lockPath}");
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException("Unable to acquire state lock: {$lockPath}");
            }

            try {
                $data = self::read($path, $default);
                $result = $callback($data);
                self::write($path, $data);
                return $result;
            } finally {
                flock($lock, LOCK_UN);
            }
        } finally {
            fclose($lock);
        }
    }

    public static function exists(string $path): bool
    {
        $backend = self::backend($path);
        return $backend === null ? is_file($path) : $backend->exists($path);
    }

    public static function verifyBackend(): void
    {
        $backend = self::backend(config('Storage')->state . '/backend-check.php');
        if ($backend !== null) {
            $backend->verify();
        }
    }

    private static function backend(string $path): ?StateBackendInterface
    {
        $driver = strtolower(trim((string)(getenv('EXTPLORER_STATE_DRIVER') ?: 'file')));
        if ($driver === 'file') {
            return null;
        }

        if (self::$backend === null || self::$backendDriver !== $driver) {
            self::$backend = StateBackendFactory::create($driver);
            self::$backendDriver = $driver;
        }

        return self::$backend;
    }

    public static function copyLegacy(string $source, string $target): void
    {
        $data = str_ends_with($source, '.json')
            ? self::readLegacyJson($source)
            : self::read($source);

        self::write($target, $data);
        self::read($target);
    }
}
