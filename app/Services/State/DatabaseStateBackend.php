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
        $this->ensureSchema();
        $key = $this->key($path);
        $payload = $this->encode($data, $path);
        $existing = $this->find($path);

        if ($existing === null) {
            $ok = $this->db->table(self::TABLE)->insert([
                'state_key' => $key,
                'state_path' => $this->normalizePath($path),
                'payload' => $payload,
                'revision' => 1,
                'updated_at' => gmdate(DATE_ATOM),
            ]);
        } else {
            $ok = $this->db->table(self::TABLE)
                ->where('state_key', $key)
                ->update([
                    'state_path' => $this->normalizePath($path),
                    'payload' => $payload,
                    'revision' => ((int)$existing['revision']) + 1,
                    'updated_at' => gmdate(DATE_ATOM),
                ]);
        }

        if ($ok === false) {
            throw new RuntimeException("Unable to write SQL state: {$path}");
        }
    }

    public function transaction(string $path, callable $callback, array $default = []): mixed
    {
        $this->ensureSchema();
        $outerTransaction = $this->db->transDepth > 0;
        if (!$outerTransaction) {
            $this->db->transBegin();
        }

        try {
            $row = $this->find($path);
            $data = $row === null ? $default : $this->decode((string)$row['payload'], $path);
            $result = $callback($data);

            $this->write($path, $data);
            if (!$outerTransaction) {
                $this->db->transCommit();
            }

            return $result;
        } catch (\Throwable $exception) {
            if (!$outerTransaction) {
                $this->db->transRollback();
            }
            throw $exception;
        }
    }

    public function exists(string $path): bool
    {
        return $this->find($path) !== null;
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
