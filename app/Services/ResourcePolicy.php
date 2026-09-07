<?php

namespace App\Services;

use RuntimeException;

final class ResourcePolicy
{
    public function maxDownloadBytes(): int
    {
        return $this->megabytes('EXTPLORER_MAX_DOWNLOAD_MB', 2048, 1, 10240);
    }

    public function maxArchiveEntries(): int
    {
        return $this->integer('EXTPLORER_ARCHIVE_MAX_ENTRIES', 100000, 1, 1000000);
    }

    public function maxArchiveBytes(): int
    {
        return $this->megabytes('EXTPLORER_ARCHIVE_MAX_EXPANDED_MB', 2048, 1, 102400);
    }

    public function maxImageBytes(): int
    {
        return $this->megabytes('EXTPLORER_IMAGE_MAX_MB', 25, 1, 1024);
    }

    public function maxImagePixels(): int
    {
        return $this->integer('EXTPLORER_IMAGE_MAX_PIXELS', 40000000, 10000, 500000000);
    }

    public function maxBackupBytes(): int
    {
        return $this->megabytes('EXTPLORER_BACKUP_MAX_MB', 102400, 1, 1048576);
    }

    public function maxArchiveRatio(): int
    {
        return $this->integer('EXTPLORER_ARCHIVE_MAX_RATIO', 200, 1, 10000);
    }

    public function copyStream($source, $destination, ?int $maxBytes = null): int
    {
        if (!is_resource($source) || !is_resource($destination)) {
            throw new RuntimeException('Invalid stream supplied.');
        }

        $limit = $maxBytes ?? $this->maxDownloadBytes();
        $copied = 0;
        while (!feof($source)) {
            $chunk = fread($source, 1024 * 1024);
            if ($chunk === false) {
                throw new RuntimeException('Unable to read stream.');
            }
            if ($chunk === '') {
                if (feof($source)) {
                    break;
                }
                continue;
            }
            $copied += strlen($chunk);
            if ($copied > $limit) {
                throw new RuntimeException('The operation exceeds the configured resource limit.');
            }
            $written = fwrite($destination, $chunk);
            if ($written !== strlen($chunk)) {
                throw new RuntimeException('Unable to write streamed data.');
            }
        }

        return $copied;
    }

    private function megabytes(string $key, int $default, int $minimum, int $maximum): int
    {
        return $this->integer($key, $default, $minimum, $maximum) * 1024 * 1024;
    }

    private function integer(string $key, int $default, int $minimum, int $maximum): int
    {
        $value = getenv($key);
        $number = $value === false || trim($value) === '' ? $default : filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number < $minimum || $number > $maximum) {
            throw new RuntimeException("{$key} must be an integer between {$minimum} and {$maximum}.");
        }

        return (int)$number;
    }
}
