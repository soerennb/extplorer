<?php

namespace App\Services\State;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

final class StorageSchemaService
{
    public function ensure(): void
    {
        $storage = config('Storage');
        $needsDatabase = $storage->stateDriver !== 'file' || $storage->sessionDriver === 'database';
        if (!$needsDatabase) {
            return;
        }

        $db = db_connect();
        $this->ensureConnection($db);

        if ($storage->stateDriver !== 'file') {
            (new DatabaseStateBackend($db))->verify();
        }
        if ($storage->sessionDriver === 'database') {
            if (!in_array($db->getPlatform(), ['MySQLi', 'Postgre'], true)) {
                throw new RuntimeException('Database sessions require MySQL/MariaDB or PostgreSQL.');
            }
            $this->ensureSessions($db);
        }
    }

    private function ensureConnection(BaseConnection $db): void
    {
        // initialize() throws a database exception when the configured
        // connection cannot be established. Keep this explicit so a selected
        // backend never silently falls back to file or dummy storage.
        $db->initialize();
    }

    private function ensureSessions(BaseConnection $db): void
    {
        $name = (string)(getenv('EXTPLORER_SESSION_TABLE') ?: 'ci_sessions');
        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $name) !== 1) {
            throw new RuntimeException('EXTPLORER_SESSION_TABLE contains invalid characters.');
        }

        $table = $db->escapeIdentifiers($db->prefixTable($name));
        $platform = strtolower($db->getPlatform());
        $sql = match (true) {
            str_contains($platform, 'sqlite') => "CREATE TABLE IF NOT EXISTS {$table} (id VARCHAR(128) PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, timestamp INTEGER NOT NULL DEFAULT 0, data BLOB NOT NULL)",
            str_contains($platform, 'postgre') => "CREATE TABLE IF NOT EXISTS {$table} (id VARCHAR(128) PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, timestamp BIGINT NOT NULL DEFAULT 0, data BYTEA NOT NULL)",
            default => "CREATE TABLE IF NOT EXISTS {$table} (id VARCHAR(128) PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, timestamp INT UNSIGNED NOT NULL DEFAULT 0, data BLOB NOT NULL)",
        };

        if ($db->query($sql) === false) {
            throw new RuntimeException('Unable to initialize the configured session table.');
        }
    }
}
