<?php

namespace App\Services;

use JsonException;
use RuntimeException;

/**
 * Reads and writes the PHP-wrapped JSON files used for persistent state.
 */
final class AtomicFileStore
{
    private const HEADER = '<?php die("Access denied"); ?>';

    public static function read(string $path, array $default = []): array
    {
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

    public static function copyLegacy(string $source, string $target): void
    {
        $data = str_ends_with($source, '.json')
            ? self::readLegacyJson($source)
            : self::read($source);

        self::write($target, $data);
        self::read($target);
    }
}
