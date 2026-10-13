<?php

namespace App\Services\VFS;

use App\Services\FileNamePolicy;
use App\Services\ResourcePolicy;
use Exception;
use ZipArchive;
use PharData;

/** Archive format safety is independent from the ordinary local file adapter. */
final class LocalArchiveService
{
    private ?\App\Services\OperationBudget $operationBudget = null;
    private int $archiveEntries = 0;
    private int $archiveBytes = 0;

    public function __construct(private LocalAdapter $fs, private ResourcePolicy $resourcePolicy) {}

    private function assertWritableTarget(string $path): void
    {
        (new FileNamePolicy())->assertSafePath($path);
    }

    public function archive(array $sources, string $destination): bool
    {
        $this->operationBudget = $this->resourcePolicy->startOperation();
        $this->archiveEntries = 0;
        $this->archiveBytes = 0;
        $fullDest = $this->fs->resolvePath($destination);
        $this->assertWritableTarget($destination);
        $ext = strtolower(pathinfo($fullDest, PATHINFO_EXTENSION));

        if (str_ends_with(strtolower($fullDest), '.tar.gz')) {
            $ext = 'tar.gz';
        }

        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($fullDest, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Cannot create zip file: $destination");
            }
            foreach ($sources as $source) {
                $fullSource = $this->fs->resolvePath($source);
                $baseName = basename($fullSource);
                if (is_dir($fullSource)) $this->addDirToZip($zip, $fullSource, $baseName);
                else {
                    $this->reserveArchive(is_file($fullSource) ? (int)filesize($fullSource) : 0);
                    if (!$zip->addFile($fullSource, $baseName)) {
                        throw new Exception("Unable to add archive source: {$source}");
                    }
                }
            }
            return $zip->close();
        } else if ($ext === 'tar' || $ext === 'tar.gz') {
            if (file_exists($fullDest)) unlink($fullDest);
            $archiveName = $ext === 'tar.gz' ? str_replace('.tar.gz', '.tar', $fullDest) : $fullDest;
            $phar = new PharData($archiveName);
            foreach ($sources as $source) {
                $fullSource = $this->fs->resolvePath($source);
                if (is_dir($fullSource)) {
                    $this->reserveTree($fullSource);
                    $phar->buildFromDirectory($fullSource);
                } else {
                    $this->reserveArchive((int)(filesize($fullSource) ?: 0));
                    $phar->addFile($fullSource, basename($fullSource));
                }
            }
            if ($ext === 'tar.gz') {
                $phar->compress(\Phar::GZ);
                unlink($archiveName);
            }
            return true;
        }
        throw new Exception("Unsupported archive format: $ext");
    }

    private function addDirToZip(ZipArchive $zip, string $dir, string $localPath)
    {
        $this->operationBudget?->tick();
        $this->reserveArchive();
        $zip->addEmptyDir($localPath);
        $files = scandir($dir);
        foreach ($files as $file) {
            $this->operationBudget?->tick();
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            $newLocalPath = $localPath . '/' . $file;
            if (is_link($fullPath)) {
                throw new Exception('Refusing to archive symbolic links.');
            }
            if (is_dir($fullPath)) {
                $this->addDirToZip($zip, $fullPath, $newLocalPath);
            } else {
                $this->reserveArchive((int)(filesize($fullPath) ?: 0));
                if (!$zip->addFile($fullPath, $newLocalPath)) {
                    throw new Exception("Unable to add archive source: {$fullPath}");
                }
            }
        }
    }

    private function reserveArchive(int $bytes = 0): void
    {
        $this->archiveEntries++;
        $this->archiveBytes += max(0, $bytes);
        if ($this->archiveEntries > $this->resourcePolicy->maxArchiveEntries()
            || $this->archiveBytes > $this->resourcePolicy->maxArchiveBytes()) {
            throw new Exception('Archive exceeds the configured safety limits.');
        }
    }

    private function reserveTree(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $this->operationBudget?->tick();
            if ($item->isLink()) {
                throw new Exception('Refusing to archive symbolic links.');
            }
            $this->reserveArchive($item->isFile() ? (int)($item->getSize() ?: 0) : 0);
        }
    }

    public function extract(string $archive, string $destination): bool
    {
        $this->operationBudget = $this->resourcePolicy->startOperation();
        $fullArchive = $this->fs->resolvePath($archive);
        if (trim(str_replace('\\', '/', $destination), '/') !== '') {
            $this->assertWritableTarget($destination);
        }
        $fullDest = $this->fs->resolvePath($destination);
        $ext = strtolower(pathinfo($fullArchive, PATHINFO_EXTENSION));

        if (str_ends_with(strtolower($fullArchive), '.tar.gz')) {
            $ext = 'tar.gz';
        }

        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($fullArchive) !== true) {
                throw new Exception("Failed to open archive: {$archive}");
            }

            $created = [];
            try {
                $this->ensureExtractionDirectory($fullDest, $created);
                $this->preflightZipEntries($zip, $fullDest);
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $this->operationBudget?->tick();
                    $this->extractZipEntry($zip, $i, $fullDest, $created);
                }
                $zip->close();
                return true;
            } catch (\Throwable $exception) {
                $zip->close();
                $this->cleanupExtractionPaths($created);
                throw $exception;
            }
        } else if ($ext === 'tar' || $ext === 'tar.gz') {
            $phar = new PharData($fullArchive);
            $created = [];
            try {
                $this->ensureExtractionDirectory($fullDest, $created);
                $result = $this->extractTarSafely($phar, $fullDest, $created);
                return $result;
            } catch (\Throwable $exception) {
                $this->cleanupExtractionPaths($created);
                throw $exception;
            }
        }

        throw new Exception("Failed to open or unsupported archive: $archive");
    }

    private function extractTarSafely(PharData $phar, string $destination, array &$created): bool
    {
        $iterator = new \RecursiveIteratorIterator($phar, \RecursiveIteratorIterator::SELF_FIRST);

        $entries = [];
        $expanded = 0;
        $archivePrefix = 'phar://' . $phar->getPath() . '/';
        foreach ($iterator as $internalPath => $entry) {
            if (!$entry instanceof \PharFileInfo) {
                continue;
            }

            // Phar iterators expose a phar:// URI as their key. Use the
            // archive-relative pathname so the path policy cannot be bypassed
            // and extraction does not recreate the URI as a directory.
            $entryPath = (string)$internalPath;
            if (str_starts_with($entryPath, $archivePrefix)) {
                $entryPath = substr($entryPath, strlen($archivePrefix));
            }
            $entryPath = $this->assertSafeArchiveEntryPath($entryPath);
            $entries[] = [$entryPath, $entry];
            $entryCount = count($entries);
            $expanded += max(0, (int)$entry->getSize());
            if ($entryCount > $this->resourcePolicy->maxArchiveEntries() || $expanded > $this->resourcePolicy->maxArchiveBytes()) {
                throw new Exception('Archive exceeds the configured safety limits.');
            }
            $this->buildSafeExtractionPath($destination, $entryPath);

            if ($entry->isDir()) {
                continue;
            }

            // Skip links to avoid extracting pointers that can escape root on later operations.
            if ($entry->isLink()) {
                throw new Exception('Archive contains symbolic link entries.');
            }

        }

        foreach ($entries as [$entryPath, $entry]) {
            $this->operationBudget?->tick();
            $targetPath = $this->buildSafeExtractionPath($destination, $entryPath);
            if ($entry->isDir()) {
                $this->ensureExtractionDirectory($targetPath, $created);
                continue;
            }

            $this->ensureExtractionDirectory(dirname($targetPath), $created);
            if (is_link($targetPath) || (file_exists($targetPath) && is_dir($targetPath))) {
                throw new Exception("Extraction target is not a regular file: {$targetPath}");
            }

            $readStream = $entry->openFile('r');
            $newFile = !file_exists($targetPath);
            if ($newFile) {
                $created[] = $targetPath;
            }
            $handle = fopen($targetPath, 'wb');
            if ($handle === false) {
                throw new Exception("Failed to write extracted file: {$targetPath}");
            }

            try {
                while (!$readStream->eof()) {
                    $this->operationBudget?->tick();
                    $chunk = $readStream->fread(8192);
                    if ($chunk === false) {
                        throw new Exception("Failed to read archive entry: {$entryPath}");
                    }
                    if ($chunk !== '' && fwrite($handle, $chunk) !== strlen($chunk)) {
                        throw new Exception("Failed to write extracted file: {$targetPath}");
                    }
                }
            } finally {
                fclose($handle);
            }
        }

        return true;
    }

    private function preflightZipEntries(ZipArchive $zip, string $destination): void
    {
        $expanded = 0;
        $paths = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $this->operationBudget?->tick();
            $rawName = $zip->getNameIndex($i);
            if (!is_string($rawName)) {
                throw new Exception('Archive contains an invalid entry name.');
            }

            $entryPath = $this->assertSafeArchiveEntryPath($rawName);
            $isDirectory = str_ends_with(str_replace('\\', '/', $rawName), '/');
            $this->buildSafeExtractionPath($destination, $entryPath);
            $pathKey = strtolower($entryPath);
            if (isset($paths[$pathKey]) && $paths[$pathKey] !== $isDirectory) {
                throw new Exception('Archive contains conflicting entry paths.');
            }
            $paths[$pathKey] = $isDirectory;

            if ($this->isZipEntrySymlink($zip, $i)) {
                throw new Exception('Archive contains symbolic link entries.');
            }
            $stat = $zip->statIndex($i);
            if (!is_array($stat)) {
                throw new Exception('Archive contains an invalid entry.');
            }
            $size = max(0, (int)$stat['size']);
            $compressed = max(0, (int)$stat['comp_size']);
            $expanded += $size;
            if ($i + 1 > $this->resourcePolicy->maxArchiveEntries()
                || $expanded > $this->resourcePolicy->maxArchiveBytes()
                || ($compressed > 0 && $size > $compressed * $this->resourcePolicy->maxArchiveRatio())) {
                throw new Exception('Archive exceeds the configured safety limits.');
            }
        }
    }

    private function extractZipEntry(ZipArchive $zip, int $index, string $destination, array &$created): void
    {
        $rawName = $zip->getNameIndex($index);
        if (!is_string($rawName)) {
            throw new Exception('Archive contains an invalid entry name.');
        }
        $entryPath = $this->assertSafeArchiveEntryPath($rawName);
        $targetPath = $this->buildSafeExtractionPath($destination, $entryPath);

        if (str_ends_with(str_replace('\\', '/', $rawName), '/')) {
            $this->ensureExtractionDirectory($targetPath, $created);
            return;
        }

        $this->ensureExtractionDirectory(dirname($targetPath), $created);
        if (is_link($targetPath) || (file_exists($targetPath) && is_dir($targetPath))) {
            throw new Exception("Extraction target is not a regular file: {$targetPath}");
        }

        $stream = $zip->getStream($rawName);
        if ($stream === false) {
            throw new Exception("Failed to read archive entry: {$entryPath}");
        }
        $newFile = !file_exists($targetPath);
        if ($newFile) {
            $created[] = $targetPath;
        }
        $handle = fopen($targetPath, 'wb');
        if ($handle === false) {
            fclose($stream);
            throw new Exception("Failed to write extracted file: {$targetPath}");
        }

        try {
            while (!feof($stream)) {
                $this->operationBudget?->tick();
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    throw new Exception("Failed to read archive entry: {$entryPath}");
                }
                if ($chunk !== '' && fwrite($handle, $chunk) !== strlen($chunk)) {
                    throw new Exception("Failed to write extracted file: {$targetPath}");
                }
            }
        } finally {
            fclose($handle);
            fclose($stream);
        }
    }

    private function isZipEntrySymlink(ZipArchive $zip, int $index): bool
    {
        $opsys = 0;
        $externalAttributes = 0;
        if (!$zip->getExternalAttributesIndex($index, $opsys, $externalAttributes)) {
            throw new Exception('Unable to inspect archive entry attributes.');
        }

        // Only Unix attributes reliably encode file type bits for symlinks.
        if ($opsys !== ZipArchive::OPSYS_UNIX || $externalAttributes === 0) {
            return false;
        }

        $mode = ($externalAttributes >> 16) & 0xF000;
        return $mode === 0xA000;
    }

    private function assertSafeArchiveEntryPath(string $entryName): string
    {
        $normalizedName = str_replace('\\', '/', trim($entryName));
        if ($normalizedName === '') {
            throw new Exception('Archive contains an empty entry path.');
        }

        if ($normalizedName[0] === '/' || preg_match('/\A[A-Za-z]:/', $normalizedName)) {
            throw new Exception('Archive contains an absolute entry path.');
        }

        try {
            $relative = PathPolicy::normalizeRelative($normalizedName);
        } catch (\RuntimeException $exception) {
            $message = str_contains(strtolower($exception->getMessage()), 'traversal')
                ? 'Archive contains path traversal entries.'
                : 'Archive contains an unsafe entry path.';
            throw new Exception($message, 0, $exception);
        }
        if ($relative === '') {
            throw new Exception('Archive contains an empty entry path.');
        }

        foreach (explode('/', $relative) as $part) {
            try {
                (new FileNamePolicy())->assertSafe($part);
            } catch (\Throwable $exception) {
                throw new Exception('Archive contains a dangerous filename.', 0, $exception);
            }
        }

        return $relative;
    }

    private function buildSafeExtractionPath(string $destination, string $entryName): string
    {
        $normalized = trim(str_replace('\\', '/', $entryName), '/');
        $parts = array_values(array_filter(
            explode('/', $normalized),
            static fn(string $part): bool => $part !== '' && $part !== '.'
        ));

        $candidate = rtrim($destination, DIRECTORY_SEPARATOR);
        if (!empty($parts)) {
            $candidate .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
        }

        $this->assertWithinDirectory($candidate, $destination);

        return $candidate;
    }

    private function assertWithinDirectory(string $candidate, string $baseDir): void
    {
        $baseReal = realpath($baseDir);
        if ($baseReal === false || !is_dir($baseReal)) {
            throw new Exception('Invalid extraction directory.');
        }
        $base = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $check = $candidate;

        while (!file_exists($check)) {
            $parent = dirname($check);
            if ($parent === $check) {
                break;
            }
            $check = $parent;
        }

        $resolved = realpath($check);
        if ($resolved === false) {
            throw new Exception('Invalid extraction path.');
        }

        $resolved = rtrim($resolved, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($resolved, $base)) {
            throw new Exception('Archive extraction path traversal blocked.');
        }

        $this->assertNoSymlinkComponents($baseReal, $candidate);
    }

    private function ensureExtractionDirectory(string $directory, array &$created): void
    {
        if (is_link($directory)) {
            throw new Exception('Pre-existing symbolic link in extraction path.');
        }
        if (file_exists($directory)) {
            if (!is_dir($directory)) {
                throw new Exception("Extraction path is not a directory: {$directory}");
            }
            $this->assertWithinDirectory($directory, $this->fs->resolvePath(''));
            return;
        }

        $parent = dirname($directory);
        if ($parent !== $directory && !file_exists($parent)) {
            $this->ensureExtractionDirectory($parent, $created);
        }
        $this->assertWithinDirectory($directory, $this->fs->resolvePath(''));
        if (!mkdir($directory, 0755) && !is_dir($directory)) {
            throw new Exception("Failed to create extraction directory: {$directory}");
        }
        $created[] = $directory;
    }

    private function assertNoSymlinkComponents(string $baseDir, string $candidate): void
    {
        $baseReal = realpath($baseDir);
        if ($baseReal === false || !is_dir($baseReal)) {
            throw new Exception('Invalid extraction directory.');
        }

        $baseReal = rtrim($baseReal, DIRECTORY_SEPARATOR);
        $candidate = rtrim($candidate, DIRECTORY_SEPARATOR);
        if ($candidate === $baseReal) {
            return;
        }
        if (!str_starts_with($candidate, $baseReal . DIRECTORY_SEPARATOR)) {
            throw new Exception('Archive extraction path traversal blocked.');
        }

        $relative = substr($candidate, strlen($baseReal) + 1);
        $current = $baseReal;
        foreach (explode(DIRECTORY_SEPARATOR, $relative) as $part) {
            if ($part === '') {
                continue;
            }
            $current .= DIRECTORY_SEPARATOR . $part;
            if (is_link($current)) {
                throw new Exception('Pre-existing symbolic link in extraction path.');
            }
        }
    }

    private function cleanupExtractionPaths(array $paths): void
    {
        usort($paths, static fn(string $left, string $right): int => strlen($right) <=> strlen($left));
        foreach (array_unique($paths) as $path) {
            if (is_link($path) || is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }
}
