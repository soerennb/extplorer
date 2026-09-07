<?php

namespace App\Services;

use App\Services\VFS\PathPolicy;
use JsonException;
use RuntimeException;
use ZipArchive;

final class StorageArchiveService
{
    private const FORMAT = 1;

    public function backup(?string $destination = null): array
    {
        $storage = config('Storage');
        $destination ??= $storage->backups . '/extplorer-backup-' . gmdate('Ymd-His') . '.zip';
        $destination = $this->normalizeDestination($destination);
        $this->ensureParent($destination);

        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create storage backup.');
        }

        $manifest = [
            'format' => self::FORMAT,
            'app_version' => config('App')->version,
            'schema_version' => DataMigrationService::CURRENT_VERSION,
            'generated_at' => gmdate(DATE_ATOM),
            'state_driver' => $storage->stateDriver,
            'session_driver' => $storage->sessionDriver,
            'files' => [],
        ];
        $policy = new ResourcePolicy();
        $totalBytes = 0;

        try {
            foreach ($this->filesToBackup($storage->root) as [$absolute, $relative]) {
                $size = filesize($absolute);
                $hash = hash_file('sha256', $absolute);
                if ($size === false || $hash === false) {
                    throw new RuntimeException("Unable to hash backup file: {$relative}");
                }
                $totalBytes += (int)$size;
                if ($totalBytes > $policy->maxBackupBytes()) {
                    throw new RuntimeException('Storage backup exceeds the configured size limit.');
                }
                if (!$zip->addFile($absolute, 'writable/' . $relative)) {
                    throw new RuntimeException("Unable to add backup file: {$relative}");
                }
                $manifest['files'][] = [
                    'path' => 'writable/' . $relative,
                    'size' => (int)$size,
                    'sha256' => $hash,
                ];
            }

            $key = trim((string)config('Encryption')->key);
            if ($key !== '') {
                $manifest['encryption_key_fingerprint'] = hash('sha256', $key);
            }
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            if (!$zip->close()) {
                throw new RuntimeException('Unable to finalize storage backup.');
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($destination);
            if ($exception instanceof JsonException) {
                throw new RuntimeException('Unable to encode storage backup manifest.', 0, $exception);
            }
            throw $exception;
        }

        return [
            'path' => $destination,
            'files' => count($manifest['files']),
            'manifest' => $manifest,
        ];
    }

    /** @return array{path:string, files:int, valid:bool} */
    public function verify(string $source): array
    {
        $zip = $this->openArchive($source);
        $policy = new ResourcePolicy();
        try {
            $manifest = $this->readManifest($zip);
            $files = $manifest['files'] ?? null;
            if (!is_array($files)) {
                throw new RuntimeException('Backup manifest does not contain a file list.');
            }
            if (count($files) > $policy->maxArchiveEntries()) {
                throw new RuntimeException('Backup contains too many files.');
            }

            $listed = [];
            $totalBytes = 0;
            foreach ($files as $entry) {
                $path = $this->validateArchivePath($entry['path'] ?? null);
                $key = strtolower($path);
                if (isset($listed[$key])) {
                    throw new RuntimeException("Backup contains a duplicate entry: {$path}");
                }
                $listed[$key] = true;
            }

            $manifestCount = 0;
            $archiveEntries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string)$zip->getNameIndex($index);
                if ($name === 'manifest.json') {
                    $manifestCount++;
                    continue;
                }
                $path = $this->validateArchivePath($name);
                if ($this->isSymlink($zip, $index) || str_ends_with($path, '/')) {
                    throw new RuntimeException("Backup contains an unsafe entry: {$path}");
                }
                $key = strtolower($path);
                if (isset($archiveEntries[$key])) {
                    throw new RuntimeException("Backup contains a duplicate entry: {$path}");
                }
                $archiveEntries[$key] = true;
            }
            ksort($archiveEntries);
            ksort($listed);
            if ($manifestCount !== 1 || $archiveEntries !== $listed) {
                throw new RuntimeException('Backup archive does not match its manifest.');
            }

            foreach ($files as $entry) {
                $path = $this->validateArchivePath($entry['path'] ?? null);
                $index = $zip->locateName($path);
                if ($index === false || $this->isSymlink($zip, $index)) {
                    throw new RuntimeException("Backup entry is missing or unsafe: {$path}");
                }
                $stat = $zip->statIndex($index);
                $compressed = max(0, (int)($stat['comp_size'] ?? 0));
                $expectedSize = (int)($entry['size'] ?? -1);
                if ($expectedSize < 0 || $expectedSize > $policy->maxBackupBytes()
                    || ($compressed > 0 && $expectedSize > $compressed * $policy->maxArchiveRatio())) {
                    throw new RuntimeException("Backup entry exceeds the configured safety limits: {$path}");
                }
                $totalBytes += $expectedSize;
                if ($totalBytes > $policy->maxBackupBytes()) {
                    throw new RuntimeException('Backup exceeds the configured size limit.');
                }
                $stream = $zip->getStream($path);
                if ($stream === false) {
                    throw new RuntimeException("Unable to read backup entry: {$path}");
                }
                $hashContext = hash_init('sha256');
                $size = 0;
                while (!feof($stream)) {
                    $chunk = fread($stream, 1024 * 1024);
                    if ($chunk === false) {
                        fclose($stream);
                        throw new RuntimeException("Unable to read backup entry: {$path}");
                    }
                    if ($chunk === '') {
                        continue;
                    }
                    hash_update($hashContext, $chunk);
                    $size += strlen($chunk);
                }
                fclose($stream);
                if ($size !== $expectedSize || !hash_equals((string)($entry['sha256'] ?? ''), hash_final($hashContext))) {
                    throw new RuntimeException("Backup checksum mismatch: {$path}");
                }
            }

            return ['path' => $source, 'files' => count($files), 'valid' => true];
        } finally {
            $zip->close();
        }
    }

    public function restore(string $source, bool $force = false): array
    {
        $storage = config('Storage');
        if (!$force) {
            throw new RuntimeException('Restore requires the explicit --force option.');
        }

        $verification = $this->verify($source);
        $this->assertEncryptionKeyCompatible($source);
        $this->assertSchemaCompatible($source);
        $preRestore = $this->backup($storage->backups . '/pre-restore-' . gmdate('Ymd-His') . '.zip');
        $stage = $storage->runtime . '/restore-' . bin2hex(random_bytes(12));
        $this->ensureDirectory($stage);

        $zip = $this->openArchive($source);
        $moved = [];
        try {
            $manifest = $this->readManifest($zip);
            foreach ($manifest['files'] as $entry) {
                $archivePath = $this->validateArchivePath($entry['path'] ?? null);
                $relative = substr($archivePath, strlen('writable/'));
                $staged = PathPolicy::resolve($stage, $relative);
                $this->extractEntry($zip, $archivePath, $staged, (int)($entry['size'] ?? 0));
            }
            $zip->close();

            foreach ($manifest['files'] as $entry) {
                $archivePath = $this->validateArchivePath($entry['path'] ?? null);
                $relative = substr($archivePath, strlen('writable/'));
                $staged = PathPolicy::resolve($stage, $relative);
                $target = PathPolicy::resolve($storage->root, $relative);
                $old = $storage->runtime . '/restore-old-' . bin2hex(random_bytes(8));
                $this->ensureParent($target);
                if (file_exists($target)) {
                    $this->ensureParent($old);
                    if (!rename($target, $old)) {
                        throw new RuntimeException("Unable to stage existing state: {$relative}");
                    }
                }
                if (!rename($staged, $target)) {
                    throw new RuntimeException("Unable to activate restored state: {$relative}");
                }
                $moved[] = [$target, $old];
            }
        } catch (\Throwable $exception) {
            $zip->close();
            $this->rollback($moved);
            $this->removeTree($stage);
            throw $exception;
        }

        foreach ($moved as [, $old]) {
            if (is_file($old)) {
                @unlink($old);
            }
        }
        $this->removeTree($stage);

        return [
            'source' => $source,
            'files' => $verification['files'],
            'pre_restore_backup' => $preRestore['path'],
        ];
    }

    /** @return iterable<array{0:string,1:string}> */
    private function filesToBackup(string $root): iterable
    {
        $excluded = ['cache', 'session', 'runtime', 'backups'];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->isLink()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($root, '/\\')) + 1));
            $first = explode('/', $relative, 2)[0] ?? '';
            if (in_array($first, $excluded, true)) {
                continue;
            }
            yield [$file->getPathname(), $relative];
        }
    }

    private function openArchive(string $source): ZipArchive
    {
        if (!is_file($source)) {
            throw new RuntimeException("Backup does not exist: {$source}");
        }
        $zip = new ZipArchive();
        if ($zip->open($source) !== true) {
            throw new RuntimeException("Unable to open backup: {$source}");
        }
        return $zip;
    }

    /** @return array<string, mixed> */
    private function readManifest(ZipArchive $zip): array
    {
        $content = $zip->getFromName('manifest.json');
        if ($content === false) {
            throw new RuntimeException('Backup manifest is missing.');
        }
        try {
            $manifest = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Backup manifest is invalid.', 0, $exception);
        }
        if (!is_array($manifest)
            || (int)($manifest['format'] ?? 0) !== self::FORMAT
            || !is_string($manifest['app_version'] ?? null)
            || trim($manifest['app_version']) === ''
            || !is_int($manifest['schema_version'] ?? null)
        ) {
            throw new RuntimeException('Unsupported backup format.');
        }
        return $manifest;
    }

    private function assertSchemaCompatible(string $source): void
    {
        $zip = $this->openArchive($source);
        try {
            $manifest = $this->readManifest($zip);
        } finally {
            $zip->close();
        }
        if ($manifest['schema_version'] !== DataMigrationService::CURRENT_VERSION) {
            throw new RuntimeException('Backup data schema is incompatible with this application version.');
        }
    }

    private function assertEncryptionKeyCompatible(string $source): void
    {
        $zip = $this->openArchive($source);
        try {
            $manifest = $this->readManifest($zip);
        } finally {
            $zip->close();
        }

        $expected = $manifest['encryption_key_fingerprint'] ?? null;
        if ($expected === null) {
            return;
        }
        $current = trim((string)config('Encryption')->key);
        if ($current === '' || !hash_equals((string)$expected, hash('sha256', $current))) {
            throw new RuntimeException('Backup encryption key does not match the configured application key.');
        }
    }

    private function validateArchivePath(mixed $path): string
    {
        if (!is_string($path) || !str_starts_with($path, 'writable/')) {
            throw new RuntimeException('Backup contains an invalid path.');
        }
        $relative = substr($path, strlen('writable/'));
        if ($relative === '' || PathPolicy::normalizeRelative($relative) !== $relative) {
            throw new RuntimeException('Backup contains a traversal path.');
        }
        return 'writable/' . $relative;
    }

    private function isSymlink(ZipArchive $zip, int $index): bool
    {
        $stat = $zip->statIndex($index);
        if (!is_array($stat) || (int)($stat['opsys'] ?? 0) !== ZipArchive::OPSYS_UNIX) {
            return false;
        }
        return (((int)($stat['external_attributes'] ?? 0) >> 16) & 0xF000) === 0xA000;
    }

    private function extractEntry(ZipArchive $zip, string $archivePath, string $target, int $expectedSize): void
    {
        $stream = $zip->getStream($archivePath);
        if ($stream === false) {
            throw new RuntimeException("Unable to extract backup entry: {$archivePath}");
        }
        $this->ensureParent($target);
        $output = fopen($target, 'wb');
        if ($output === false) {
            fclose($stream);
            throw new RuntimeException("Unable to stage backup entry: {$archivePath}");
        }
        try {
            $copied = (new ResourcePolicy())->copyStream($stream, $output, (new ResourcePolicy())->maxBackupBytes());
            if ($copied !== $expectedSize) {
                throw new RuntimeException("Backup entry size mismatch: {$archivePath}");
            }
            chmod($target, 0600);
        } finally {
            fclose($stream);
            fclose($output);
        }
    }

    private function normalizeDestination(string $destination): string
    {
        $destination = rtrim($destination, '/\\');
        if ($destination === '') {
            throw new RuntimeException('Backup destination is empty.');
        }
        return $destination;
    }

    private function ensureParent(string $path): void
    {
        $directory = dirname($path);
        $this->ensureDirectory($directory);
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
            throw new RuntimeException("Unable to create directory: {$path}");
        }
    }

    /** @param list<array{0:string,1:string}> $moved */
    private function rollback(array $moved): void
    {
        foreach (array_reverse($moved) as [$target, $old]) {
            if (file_exists($target)) {
                @unlink($target);
            }
            if (file_exists($old)) {
                @rename($old, $target);
            }
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $child = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($child) && !is_link($child)) {
                $this->removeTree($child);
            } else {
                @unlink($child);
            }
        }
        @rmdir($path);
    }
}
