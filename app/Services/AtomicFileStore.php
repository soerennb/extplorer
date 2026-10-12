<?php

namespace App\Services;

use App\Services\State\FileStateBackend;
use App\Services\State\StateBackendFactory;
use App\Services\State\StateBackendInterface;
use JsonException;
use RuntimeException;

/** Backwards-compatible facade for the selected metadata backend. */
final class AtomicFileStore
{
    private static ?StateBackendInterface $backend = null;
    private static ?string $backendDriver = null;

    public static function read(string $path, array $default = []): array { return self::backend()->read($path, $default); }
    public static function write(string $path, array $data): void { self::backend()->write($path, $data); }
    public static function exists(string $path): bool { return self::backend()->exists($path); }
    public static function delete(string $path): void { self::backend()->delete($path); }
    public static function transaction(string $path, callable $callback, array $default = []): mixed { return self::backend()->transaction($path, $callback, $default); }
    public static function readMany(array $defaults): array { return self::backend()->readMany($defaults); }
    public static function transactionMany(array $defaults, callable $callback): mixed { return self::backend()->transactionMany($defaults, $callback); }
    public static function verifyBackend(): void { self::backend()->verify(); }

    public static function reset(): void
    {
        self::$backend = null;
        self::$backendDriver = null;
    }

    private static function backend(): StateBackendInterface
    {
        $driver = strtolower(trim((string)(getenv('EXTPLORER_STATE_DRIVER') ?: 'file')));
        if (self::$backend === null || self::$backendDriver !== $driver) {
            self::$backend = StateBackendFactory::create($driver);
            self::$backendDriver = $driver;
        }
        return self::$backend;
    }

    public static function readLegacyProtected(string $path): array
    {
        return (new FileStateBackend())->read($path);
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

    public static function copyLegacy(string $source, string $target): void
    {
        $data = str_ends_with($source, '.json') ? self::readLegacyJson($source) : self::readLegacyProtected($source);
        self::write($target, $data);
        self::read($target);
    }
}
