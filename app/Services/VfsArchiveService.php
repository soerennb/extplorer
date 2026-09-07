<?php

namespace App\Services;

use App\Services\VFS\IFileSystem;
use RuntimeException;
use ZipArchive;

/**
 * Creates download archives through the VFS, including remote adapters.
 */
final class VfsArchiveService
{
    private int $entries = 0;
    private int $bytes = 0;
    private ResourcePolicy $policy;
    private string $stagingDirectory = '';
    private array $stagedFiles = [];

    public function __construct(?ResourcePolicy $policy = null)
    {
        $this->policy = $policy ?? new ResourcePolicy();
    }

    public function createZip(IFileSystem $fs, array $sources, string $destination): void
    {
        $this->entries = 0;
        $this->bytes = 0;
        $this->stagingDirectory = dirname($destination);
        $this->stagedFiles = [];
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create download archive.');
        }

        try {
            foreach ($sources as $source) {
                $source = (string)$source;
                $metadata = $fs->getMetadata($source);
                if (!is_array($metadata)) {
                    throw new RuntimeException('Archive source was not found.');
                }

                $entryName = $this->safeComponent(basename(trim(str_replace('\\', '/', $source), '/')) ?: 'files');
                if (($metadata['type'] ?? '') === 'dir') {
                    $this->addDirectory($zip, $fs, $source, $entryName);
                } else {
                    $this->addFile($zip, $fs, $source, $entryName);
                }
            }

            if (!$zip->close()) {
                throw new RuntimeException('Cannot finalize download archive.');
            }
            $this->cleanupStagedFiles();
        } catch (\Throwable $exception) {
            $zip->close();
            $this->cleanupStagedFiles();
            @unlink($destination);
            throw $exception;
        }
    }

    private function addDirectory(ZipArchive $zip, IFileSystem $fs, string $path, string $entryName): void
    {
        $this->reserveEntry();
        if (!$zip->addEmptyDir($entryName)) {
            throw new RuntimeException('Cannot add directory to download archive.');
        }

        foreach ($fs->listDirectory($path) as $item) {
            $name = $this->safeComponent((string)($item['name'] ?? ''));
            $childPath = rtrim($path, '/') . '/' . $name;
            $childEntry = $entryName . '/' . $name;
            if (($item['type'] ?? '') === 'dir') {
                $this->addDirectory($zip, $fs, $childPath, $childEntry);
            } else {
                $this->addFile($zip, $fs, $childPath, $childEntry);
            }
        }
    }

    private function addFile(ZipArchive $zip, IFileSystem $fs, string $path, string $entryName): void
    {
        $metadata = $fs->getMetadata($path);
        $declaredSize = max(0, (int)($metadata['size'] ?? 0));
        $this->reserveEntry($declaredSize);

        // Local adapters can hand ZipArchive a filename, allowing libzip to
        // stream the source instead of materializing it in PHP memory.
        if (method_exists($fs, 'resolvePath')) {
            try {
                $fullPath = $fs->resolvePath($path);
                if (is_file($fullPath) && $zip->addFile($fullPath, $entryName)) {
                    return;
                }
            } catch (\Throwable $exception) {
                // Remote adapters intentionally do not expose physical paths.
            }
        }

        $temporary = tempnam($this->stagingDirectory, '.extplorer-archive-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to stage remote archive source.');
        }
        $this->stagedFiles[] = $temporary;

        $source = null;
        $target = null;
        try {
            $source = $fs->openReadStream($path);
            $target = fopen($temporary, 'wb');
            if ($target === false) {
                throw new RuntimeException('Unable to stage remote archive source.');
            }
            $remainingBytes = $this->policy->maxArchiveBytes() - $this->bytes + $declaredSize;
            $copied = $this->policy->copyStream($source, $target, max(0, $remainingBytes));
        } finally {
            if (is_resource($source)) fclose($source);
            if (is_resource($target)) fclose($target);
        }

        if ($copied !== $declaredSize && $declaredSize > 0) {
            throw new RuntimeException('Download source changed while creating archive.');
        }
        if ($declaredSize === 0) {
            $this->reserveBytes($copied);
        }
        if (!$zip->addFile($temporary, $entryName)) {
            throw new RuntimeException('Cannot add file to download archive.');
        }
    }

    private function cleanupStagedFiles(): void
    {
        foreach ($this->stagedFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->stagedFiles = [];
    }

    private function reserveEntry(int $bytes = 0): void
    {
        $this->entries++;
        if ($this->entries > $this->policy->maxArchiveEntries()) {
            throw new RuntimeException('Download archive exceeds the configured safety limits.');
        }
        $this->reserveBytes($bytes);
    }

    private function reserveBytes(int $bytes): void
    {
        $this->bytes += max(0, $bytes);
        if ($this->bytes > $this->policy->maxArchiveBytes()) {
            throw new RuntimeException('Download archive exceeds the configured safety limits.');
        }
    }

    private function safeComponent(string $name): string
    {
        if ($name === '' || $name === '.' || $name === '..' || preg_match('~[\x00-\x1F\x7F\\\\/]~', $name) === 1) {
            throw new RuntimeException('Archive contains an invalid path component.');
        }
        return $name;
    }
}
