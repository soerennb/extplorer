<?php

namespace App\Services\State;

use JsonException;
use RuntimeException;

/** Protected JSON documents with coordinated, recoverable read/modify/write. */
final class FileStateBackend implements StateBackendInterface
{
    private const HEADER = '<?php die("Access denied"); ?>';
    private array $lockedDirectories = [];

    public function read(string $path, array $default = []): array
    {
        if (!is_dir(dirname($path))) return $default;
        return $this->locked(dirname($path), fn() => $this->readDocument($path, $default));
    }

    public function write(string $path, array $data): void
    {
        $this->locked(dirname($path), fn() => $this->writeDocument($path, $data));
    }

    public function delete(string $path): void
    {
        if (!is_dir(dirname($path))) return;
        $this->locked(dirname($path), function () use ($path): void {
            if (is_file($path) && !unlink($path)) throw new RuntimeException("Unable to delete persistent state: {$path}");
        });
    }

    public function exists(string $path): bool
    {
        if (!is_dir(dirname($path))) return false;
        return $this->locked(dirname($path), fn() => is_file($path));
    }

    public function verify(): void {}

    public function readMany(array $defaults): array
    {
        if ($defaults === []) return [];
        return $this->locked($this->directory($defaults), function () use ($defaults): array {
            $result = [];
            foreach ($defaults as $path => $default) $result[$path] = $this->readDocument($path, $default);
            return $result;
        });
    }

    public function transaction(string $path, callable $callback, array $default = []): mixed
    {
        return $this->transactionMany([$path => $default], function (array &$documents) use ($path, $callback): mixed {
            return $callback($documents[$path]);
        });
    }

    public function transactionMany(array $defaults, callable $callback): mixed
    {
        $directory = $this->directory($defaults);
        return $this->locked($directory, function () use ($defaults, $callback, $directory): mixed {
            $documents = $this->readMany($defaults);
            $result = $callback($documents);
            if (array_keys($documents) !== array_keys($defaults)) throw new RuntimeException('State transaction changed its document set.');
            $previous = [];
            foreach ($documents as $path => $data) {
                if (!is_array($data)) throw new RuntimeException('State documents must be arrays.');
                $previous[basename($path)] = is_file($path) ? $this->readDocument($path) : null;
            }
            $journal = $directory . '/.extplorer-state-journal.php';
            $this->writeDocument($journal, $previous);
            try {
                foreach ($documents as $path => $data) $this->writeDocument($path, $data);
                if (!unlink($journal)) throw new RuntimeException('Unable to commit state journal.');
            } catch (\Throwable $exception) {
                $this->recover($directory);
                throw $exception;
            }
            return $result;
        });
    }

    private function directory(array $documents): string
    {
        if ($documents === []) throw new RuntimeException('A state transaction needs documents.');
        $directories = array_unique(array_map('dirname', array_keys($documents)));
        if (count($directories) !== 1) throw new RuntimeException('Related state documents must share a directory.');
        return reset($directories);
    }

    private function locked(string $directory, callable $callback): mixed
    {
        if (isset($this->lockedDirectories[$directory])) return $callback();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException("Unable to create state directory: {$directory}");
        $lock = fopen($directory . '/.extplorer-state.lock', 'c');
        if ($lock === false) throw new RuntimeException('Unable to open state coordination lock.');
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException('Unable to lock state documents.');
            $this->lockedDirectories[$directory] = true;
            try {
                $this->recover($directory);
                return $callback();
            } finally {
                unset($this->lockedDirectories[$directory]);
                flock($lock, LOCK_UN);
            }
        } finally {
            fclose($lock);
        }
    }

    private function recover(string $directory): void
    {
        $journal = $directory . '/.extplorer-state-journal.php';
        if (!is_file($journal)) return;
        foreach ($this->readDocument($journal) as $name => $previous) {
            if (!is_string($name) || basename($name) !== $name || $name === '.' || $name === '..' || str_contains($name, '\\') || str_starts_with($name, '.extplorer-state')) throw new RuntimeException('Invalid state recovery journal.');
            $path = $directory . '/' . $name;
            if ($previous === null) {
                if (is_file($path) && !unlink($path)) throw new RuntimeException('Unable to recover new state document.');
            } elseif (is_array($previous)) {
                $this->writeDocument($path, $previous);
            } else {
                throw new RuntimeException('Invalid state recovery document.');
            }
        }
        if (!unlink($journal)) throw new RuntimeException('Unable to finish state recovery.');
    }

    private function readDocument(string $path, array $default = []): array
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

    private function writeDocument(string $path, array $data): void
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

}
