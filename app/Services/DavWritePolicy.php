<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class DavWritePolicy
{
    public function __construct(
        private string $username,
        private string $root,
        private string $allowedExtensions,
        private string $blockedExtensions,
        private int $maxBytes,
        private int $quotaBytes,
    ) {
        $this->root = rtrim((string)(realpath($root) ?: $root), '/\\');
    }

    public function assertFilename(string $filename): void
    {
        if (!(new FileNamePolicy())->isAllowed($filename, $this->allowedExtensions, $this->blockedExtensions)) {
            throw new RuntimeException('WebDAV filename is not allowed by the account policy.');
        }
    }

    /** @param resource|string|null $data */
    public function writeAtomic(string $target, $data): int
    {
        $this->assertFilename(basename($target));
        $directory = dirname($target);
        $temporary = tempnam($directory, '.extplorer-dav-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to create WebDAV upload staging file.');
        }

        try {
            $input = is_resource($data) ? $data : fopen('php://temp', 'w+b');
            if (!is_resource($input)) {
                throw new RuntimeException('Unable to open WebDAV upload stream.');
            }
            $closeInput = !is_resource($data);
            if ($closeInput && is_string($data) && $data !== '') {
                fwrite($input, $data);
                rewind($input);
            }
            $output = fopen($temporary, 'wb');
            if ($output === false) {
                if ($closeInput) fclose($input);
                throw new RuntimeException('Unable to open WebDAV staging stream.');
            }
            $written = 0;
            try {
                while (!feof($input)) {
                    $chunk = fread($input, 1024 * 1024);
                    if ($chunk === false) throw new RuntimeException('Unable to read WebDAV upload stream.');
                    $written += strlen($chunk);
                    if ($this->maxBytes > 0 && $written > $this->maxBytes) {
                        throw new RuntimeException('WebDAV upload exceeds the configured size limit.');
                    }
                    if ($chunk !== '' && fwrite($output, $chunk) !== strlen($chunk)) {
                        throw new RuntimeException('Unable to write WebDAV staging file.');
                    }
                }
            } finally {
                fclose($output);
                if ($closeInput) fclose($input);
            }

            $this->withQuotaLock(function () use ($target, $temporary, $written): void {
                $existing = is_file($target) ? (int)(filesize($target) ?: 0) : 0;
                if ($this->quotaBytes > 0 && $this->directorySize($this->root) - $existing + $written > $this->quotaBytes) {
                    throw new RuntimeException('WebDAV upload would exceed the account quota.');
                }
                if (!chmod($temporary, 0640) || !rename($temporary, $target)) {
                    throw new RuntimeException('Unable to activate WebDAV upload.');
                }
                clearstatcache(true, $target);
            });

            return $written;
        } finally {
            if (is_file($temporary)) @unlink($temporary);
        }
    }

    private function withQuotaLock(callable $callback): void
    {
        $dir = (new \Config\Storage())->cache . '/dav';
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create WebDAV lock directory.');
        }
        $lock = fopen($dir . '/quota-' . hash('sha256', $this->username) . '.lock', 'c');
        if ($lock === false) throw new RuntimeException('Unable to open WebDAV quota lock.');
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException('Unable to acquire WebDAV quota lock.');
            $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function directorySize(string $path): int
    {
        $total = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $item) {
            if ($item->isLink()) continue;
            if (str_starts_with($item->getFilename(), '.extplorer-dav-')) continue;
            if ($item->isFile()) $total += $item->getSize();
        }
        return $total;
    }
}
