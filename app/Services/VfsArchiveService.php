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
    private const MAX_ENTRIES = 100000;
    private const MAX_BYTES = 2147483648;

    private int $entries = 0;
    private int $bytes = 0;

    public function createZip(IFileSystem $fs, array $sources, string $destination): void
    {
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
        } catch (\Throwable $exception) {
            $zip->close();
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

        $content = $fs->readFile($path);
        if (strlen($content) !== $declaredSize && $declaredSize > 0) {
            throw new RuntimeException('Download source changed while creating archive.');
        }
        if (!$zip->addFromString($entryName, $content)) {
            throw new RuntimeException('Cannot add file to download archive.');
        }
    }

    private function reserveEntry(int $bytes = 0): void
    {
        $this->entries++;
        $this->bytes += $bytes;
        if ($this->entries > self::MAX_ENTRIES || $this->bytes > self::MAX_BYTES) {
            throw new RuntimeException('Download archive exceeds the configured safety limits.');
        }
    }

    private function safeComponent(string $name): string
    {
        if ($name === '' || $name === '.' || $name === '..' || preg_match('/[\x00-\x1F\x7F\\\/]/', $name) === 1) {
            throw new RuntimeException('Archive contains an invalid path component.');
        }
        return $name;
    }
}
