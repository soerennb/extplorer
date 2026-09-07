<?php

namespace App\Services;

use RuntimeException;
use App\Services\State\StorageSchemaService;

/**
 * Versioned migrations for the file-backed application state.
 */
final class DataMigrationService
{
    public const CURRENT_VERSION = 4;

    private string $migrationState;
    private string $backupDirectory;

    public function __construct()
    {
        $storage = config('Storage');
        $this->migrationState = $storage->state . '/migrations.php';
        $this->backupDirectory = $storage->backups . '/migration-' . gmdate('Ymd-His');
    }

    public function run(): int
    {
        (new StorageSchemaService())->ensure();

        return AtomicFileStore::transaction(
            $this->migrationState,
            fn(array &$state): int => $this->runLocked($state),
            ['version' => 0, 'applied' => []]
        );
    }

    private function runLocked(array &$state): int
    {
        $this->ensureDirectories();
        $version = (int)($state['version'] ?? 0);
        $applied = is_array($state['applied'] ?? null) ? $state['applied'] : [];

        // Run this scan on every boot. It also repairs interrupted migrations
        // that were unable to update the version marker.
        $this->migrateLegacyFiles();
        if ($version < 1) {
            $version = 1;
            $applied[] = 'storage-layout-v1';
        }

        if ($version < 2) {
            $version = 2;
            $applied[] = 'mount-secrets-v2';
        }

        if ($version < 3) {
            (new \App\Models\UserModel())->migratePasswordState();
            $version = 3;
            $applied[] = 'explicit-password-state-v3';
        }

        if ($version < 4) {
            (new \App\Models\UserModel())->migratePasswordState();
            $version = 4;
            $applied[] = 'account-auth-state-v4';
        }

        // Keep this idempotent and run it on every boot. This also handles a
        // mount added by an older release after the schema marker was written.
        (new MountService())->migrateSecrets();

        // Ensure all current metadata files and per-user trash indexes exist.
        $userModel = new \App\Models\UserModel();
        $userModel->getRoles();
        new ShareService();
        new MountService();
        LogService::getLogs();
        foreach ($userModel->getUsers() as $user) {
            if (!empty($user['username'])) {
                new TrashService((string)$user['username']);
            }
        }

        if ($version !== self::CURRENT_VERSION) {
            throw new RuntimeException("Unsupported data schema version: {$version}");
        }

        $state = [
            'version' => $version,
            'applied' => array_values(array_unique($applied)),
            'updated_at' => gmdate(DATE_ATOM),
        ];
        return $version;
    }

    private function ensureDirectories(): void
    {
        $storage = config('Storage');
        foreach ([
            $storage->root,
            $storage->state,
            $storage->logs,
            $storage->session,
            $storage->uploads,
            $storage->uploads . '/temp',
            $storage->uploads . '/shares',
            $storage->uploads . '/chunks',
            $storage->fileManagerRoot,
            $storage->shared,
            $storage->trash,
            $storage->versions,
            $storage->cache,
            $storage->cache . '/thumbs',
            $storage->cache . '/dav',
            $storage->runtime,
            $storage->backups,
        ] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException("Unable to create storage directory: {$directory}");
            }
        }
    }

    private function migrateLegacyFiles(): void
    {
        $storage = config('Storage');
        $files = [
            'users' => [$storage->state . '/users.php', $storage->root . '/users.json', $storage->root . '/users.php'],
            'roles' => [$storage->state . '/roles.php', $storage->root . '/roles.json', $storage->root . '/roles.php'],
            'groups' => [$storage->state . '/groups.php', $storage->root . '/groups.json', $storage->root . '/groups.php'],
            'shares' => [$storage->state . '/shares.php', $storage->root . '/shares.json', $storage->root . '/shares.php'],
            'settings' => [$storage->state . '/settings.php', $storage->root . '/settings.json', $storage->root . '/settings.php'],
            'mounts' => [$storage->state . '/mounts.php', $storage->root . '/mounts.json', $storage->root . '/mounts.php'],
            'remember_tokens' => [$storage->state . '/remember_tokens.php', $storage->root . '/remember_tokens.json', $storage->root . '/remember_tokens.php'],
            'activity_logs' => [$storage->logs . '/activity_logs.php', $storage->root . '/activity_logs.json', $storage->root . '/activity_logs.php'],
        ];

        foreach ($files as $name => [$target, $legacyJson, $legacyProtected]) {
            $this->migrateFile($name, $target, $legacyJson, $legacyProtected);
        }

        $legacyCleanup = $storage->root . '/last_cleanup.txt';
        $newCleanup = $storage->runtime . '/last_cleanup.txt';
        if (is_file($legacyCleanup) && !is_file($newCleanup)) {
            $this->backup($legacyCleanup, 'last_cleanup.txt');
            if (!copy($legacyCleanup, $newCleanup)) {
                throw new RuntimeException("Unable to migrate cleanup marker: {$legacyCleanup}");
            }
            if (!unlink($legacyCleanup)) {
                throw new RuntimeException("Unable to remove legacy cleanup marker: {$legacyCleanup}");
            }
        }

        foreach (glob($storage->trash . '/*/index.json') ?: [] as $legacyIndex) {
            $target = dirname($legacyIndex) . '/index.php';
            $this->migrateFile('trash-index', $target, $legacyIndex, null);
        }
    }

    private function migrateFile(string $name, string $target, ?string $legacyJson, ?string $legacyProtected): void
    {
        $sources = [];
        foreach ([$legacyJson, $legacyProtected] as $candidate) {
            if ($candidate !== null && is_file($candidate)) {
                $sources[] = $candidate;
            }
        }
        if ($sources === []) {
            return;
        }

        $sourceData = null;
        foreach ($sources as $source) {
            $this->backup($source, $name . '-' . md5($source) . '-' . basename($source));
            $data = str_ends_with($source, '.json')
                ? AtomicFileStore::readLegacyJson($source)
                : AtomicFileStore::read($source);
            if ($sourceData !== null && $sourceData !== $data) {
                throw new RuntimeException("Conflicting legacy {$name} state files detected.");
            }
            $sourceData = $data;
        }

        if (AtomicFileStore::exists($target)) {
            $targetData = AtomicFileStore::read($target);
            if ($sourceData !== $targetData) {
                throw new RuntimeException("Conflicting legacy and migrated {$name} state detected.");
            }
        } else {
            AtomicFileStore::write($target, $sourceData ?? []);
            AtomicFileStore::read($target);
        }

        foreach ($sources as $source) {
            if (!unlink($source)) {
                throw new RuntimeException("Unable to remove legacy state file: {$source}");
            }
        }
    }

    private function backup(string $source, string $name): void
    {
        if (!is_dir($this->backupDirectory) && !mkdir($this->backupDirectory, 0700, true) && !is_dir($this->backupDirectory)) {
            throw new RuntimeException("Unable to create migration backup directory: {$this->backupDirectory}");
        }
        $target = $this->backupDirectory . '/' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        if (!copy($source, $target)) {
            throw new RuntimeException("Unable to back up legacy state file: {$source}");
        }
        chmod($target, 0600);
    }

}
