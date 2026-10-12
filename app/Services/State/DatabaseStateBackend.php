<?php

namespace App\Services\State;

use CodeIgniter\Database\BaseConnection;
use JsonException;
use RuntimeException;

/**
 * Stores eXtplorer's small metadata documents in a transactional SQL table.
 * File contents, uploads and the file-manager root remain filesystem data.
 */
final class DatabaseStateBackend implements StateBackendInterface
{
    private const TABLE = 'extplorer_state';

    private BaseConnection $db;
    private string $table;
    private bool $schemaReady = false;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
        $this->table = $this->db->prefixTable(self::TABLE);
    }

    public function read(string $path, array $default = []): array
    {
        $row = $this->find($path);
        if ($row === null) {
            return $default;
        }

        return $this->decode((string)$row['payload'], $path);
    }

    public function write(string $path, array $data): void
    {
        $this->locked([dirname($path)], fn() => $this->writeDocument($path, $data));
    }

    private function writeDocument(string $path, array $data): void
    {
        $key = $this->key($path);
        $existing = $this->find($path);
        $record = [
            'state_path' => $this->normalizePath($path),
            'payload' => $this->encode($data, $path),
            'revision' => (int)($existing['revision'] ?? 0) + 1,
            'updated_at' => gmdate(DATE_ATOM),
        ];
        $builder = $this->db->table(self::TABLE);
        $ok = $existing === null ? $builder->insert(['state_key' => $key] + $record) : $builder->where('state_key', $key)->update($record);
        if ($ok === false) throw new RuntimeException("Unable to write SQL state: {$path}");
    }

    public function transaction(string $path, callable $callback, array $default = []): mixed
    {
        return $this->transactionMany([$path => $default], function (array &$documents) use ($path, $callback): mixed {
            return $callback($documents[$path]);
        });
    }

    public function readMany(array $defaults): array
    {
        if ($defaults === []) return [];
        return $this->locked(array_map('dirname', array_keys($defaults)), function () use ($defaults): array {
            $documents = [];
            foreach ($defaults as $path => $default) $documents[$path] = $this->read($path, $default);
            return $documents;
        });
    }

    public function transactionMany(array $defaults, callable $callback): mixed
    {
        if ($defaults === []) throw new RuntimeException('A state transaction needs documents.');
        return $this->locked(array_map('dirname', array_keys($defaults)), function () use ($defaults, $callback): mixed {
            $documents = $this->readMany($defaults);
            $result = $callback($documents);
            if (array_keys($documents) !== array_keys($defaults)) throw new RuntimeException('State transaction changed its document set.');
            foreach ($documents as $path => $data) $this->writeDocument($path, $data);
            return $result;
        });
    }

    /** Serialize metadata mutations across processes, including absent documents. */
    private function locked(array $directories, callable $callback): mixed
    {
        $this->ensureSchema();
        $outer = $this->db->transDepth > 0;
        if (!$outer && !$this->db->transBegin()) throw new RuntimeException('Unable to start SQL state transaction.');
        try {
            $directories = array_values(array_unique($directories));
            sort($directories, SORT_STRING);
            $table = $this->db->escapeIdentifiers($this->table);
            $sqlite = str_contains(strtolower($this->db->getPlatform()), 'sqlite');
            $mysql = str_contains(strtolower($this->db->getPlatform()), 'mysql');
            foreach ($directories as $directory) {
                $key = hash('sha256', 'extplorer-state-lock:' . $this->normalizePath($directory));
                $insert = $mysql
                    ? "INSERT IGNORE INTO {$table} (state_key,state_path,payload,revision,updated_at) VALUES (?,?,?,?,?)"
                    : "INSERT INTO {$table} (state_key,state_path,payload,revision,updated_at) VALUES (?,?,?,?,?) ON CONFLICT (state_key) DO NOTHING";
                if ($this->db->query($insert, [$key, $directory . '/.extplorer-state.lock', '{}', 0, gmdate(DATE_ATOM)]) === false) throw new RuntimeException('Unable to initialize SQL state lock.');
                // SQLite has no row locks. Acquiring a write reservation before
                // reading also avoids deferred read-to-write upgrade failures.
                $lock = $sqlite
                    ? "UPDATE {$table} SET revision=revision WHERE state_key=?"
                    : "SELECT state_key FROM {$table} WHERE state_key=? FOR UPDATE";
                if ($this->db->query($lock, [$key]) === false) throw new RuntimeException('Unable to acquire SQL state lock.');
            }
            $result = $callback();
            if (!$outer && !$this->db->transCommit()) throw new RuntimeException('Unable to commit SQL state transaction.');
            return $result;
        } catch (\Throwable $exception) {
            if (!$outer) $this->db->transRollback();
            throw $exception;
        }
    }

    public function exists(string $path): bool
    {
        return $this->find($path) !== null;
    }

    public function delete(string $path): void
    {
        $this->locked([dirname($path)], function () use ($path): void {
            if (!$this->db->table(self::TABLE)->where('state_key', $this->key($path))->delete()) throw new RuntimeException("Unable to delete SQL state: {$path}");
        });
    }

    public function verify(): void
    {
        $this->ensureSchema();
        $query = $this->db->table(self::TABLE)->limit(1)->get();
        if ($query === false) {
            throw new RuntimeException('Unable to query SQL state backend.');
        }
        $query->getResultArray();
    }

    private function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $table = $this->db->escapeIdentifiers($this->table);
        $platform = strtolower($this->db->getPlatform());
        $sql = match (true) {
            str_contains($platform, 'sqlite') => "CREATE TABLE IF NOT EXISTS {$table} (state_key VARCHAR(64) PRIMARY KEY, state_path TEXT NOT NULL, payload TEXT NOT NULL, revision INTEGER NOT NULL DEFAULT 1, updated_at VARCHAR(40) NOT NULL)",
            str_contains($platform, 'postgre') => "CREATE TABLE IF NOT EXISTS {$table} (state_key VARCHAR(64) PRIMARY KEY, state_path TEXT NOT NULL, payload TEXT NOT NULL, revision BIGINT NOT NULL DEFAULT 1, updated_at VARCHAR(40) NOT NULL)",
            default => "CREATE TABLE IF NOT EXISTS {$table} (state_key VARCHAR(64) PRIMARY KEY, state_path TEXT NOT NULL, payload LONGTEXT NOT NULL, revision BIGINT NOT NULL DEFAULT 1, updated_at VARCHAR(40) NOT NULL)",
        };

        if ($this->db->query($sql) === false) {
            throw new RuntimeException('Unable to initialize SQL state backend.');
        }
        $this->schemaReady = true;
    }

    /** @return array<string, mixed>|null */
    private function find(string $path): ?array
    {
        $this->ensureSchema();
        $row = $this->db->table(self::TABLE)
            ->where('state_key', $this->key($path))
            ->get()
            ->getRowArray();

        return $row === null ? null : $row;
    }

    private function key(string $path): string
    {
        return hash('sha256', $this->normalizePath($path));
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function encode(array $data, string $path): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Unable to encode SQL state: {$path}", 0, $exception);
        }
    }

    private function decode(string $payload, string $path): array
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid SQL state: {$path}", 0, $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException("SQL state must contain an object or array: {$path}");
        }

        return $data;
    }
}
