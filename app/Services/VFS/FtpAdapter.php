<?php

namespace App\Services\VFS;

use Exception;

class FtpAdapter implements IFileSystem
{
    private $conn;
    private string $root;
    private string $host;
    private int $port;

    public function __construct(string $host, string $user, string $pass, int $port = 21, string $root = '/')
    {
        if (!function_exists('ftp_connect')) {
            throw new Exception("FTP extension not installed");
        }
        $this->host = $host;
        $this->port = $port;
        $this->root = '/' . trim($root, '/');

        $this->conn = ftp_connect($host, $port);
        if (!$this->conn) throw new Exception("Could not connect to FTP host: $host");

        if (!@ftp_login($this->conn, $user, $pass)) {
            throw new Exception("FTP Login failed for user: $user");
        }

        ftp_pasv($this->conn, true);
    }

    public function __destruct()
    {
        if ($this->conn) ftp_close($this->conn);
    }

    private function resolvePath(string $path): string
    {
        return $this->root . ($this->root === '/' ? '' : '/') . trim($path, '/');
    }

    public function listDirectory(string $path, bool $showHidden = true): array
    {
        $fullPath = $this->resolvePath($path);
        $raw = ftp_rawlist($this->conn, $fullPath);
        if ($raw === false) return [];

        $results = [];
        foreach ($raw as $line) {
            $data = $this->parseRawList($line);
            if (!$data || $data['name'] === '.' || $data['name'] === '..') continue;
            if (!$showHidden && str_starts_with($data['name'], '.')) continue;
            
            $itemRelPath = ($path === '' || $path === '/' ? '' : trim($path, '/') . '/') . $data['name'];
            
            $results[] = [
                'name' => $data['name'],
                'path' => $itemRelPath,
                'type' => $data['type'],
                'size' => $data['size'],
                'mtime' => $data['mtime'],
                'perms' => $data['perms'],
                'owner' => $data['owner'],
                'group' => $data['group'],
                'extension' => $data['type'] === 'dir' ? null : pathinfo($data['name'], PATHINFO_EXTENSION),
                'mime' => $data['type'] === 'dir' ? 'directory' : 'application/octet-stream' // FTP doesn't easily give mime
            ];
        }
        return $results;
    }

    private function parseRawList(string $line): ?array
    {
        // Simple Linux-style parser (DRWXRWXRWX ...)
        // Example: drwxr-xr-x    2 1000     1000         4096 Jan 01 12:34 Documents
        if (preg_match('/^([d-][rwx-]{9})\s+(\d+)\s+(\w+)\s+(\w+)\s+(\d+)\s+(\w+\s+\d+\s+[\d:]+)\s+(.*)$/', $line, $matches)) {
            return [
                'perms' => $matches[1],
                'owner' => $matches[3],
                'group' => $matches[4],
                'size' => (int)$matches[5],
                'mtime' => strtotime($matches[6]),
                'name' => $matches[7],
                'type' => str_starts_with($matches[1], 'd') ? 'dir' : 'file'
            ];
        }
        return null;
    }

    public function readFile(string $path): string
    {
        $temp = fopen('php://temp', 'r+');
        if (ftp_fget($this->conn, $temp, $this->resolvePath($path), FTP_BINARY)) {
            rewind($temp);
            return stream_get_contents($temp);
        }
        throw new Exception("Could not read FTP file: $path");
    }

    public function writeFile(string $path, string $content): bool
    {
        $temp = fopen('php://temp', 'r+');
        fwrite($temp, $content);
        rewind($temp);
        return ftp_fput($this->conn, $this->resolvePath($path), $temp, FTP_BINARY);
    }

    public function delete(string $path): bool
    {
        $full = $this->resolvePath($path);
        
        // Use rawlist to check if it's a directory
        $raw = @ftp_rawlist($this->conn, $full);
        if ($raw !== false) {
            // It's a directory (or file that returns list, but we'll try recursive)
            return $this->deleteRecursive($full);
        }
        
        return @ftp_delete($this->conn, $full);
    }

    private function deleteRecursive(string $fullPath): bool
    {
        $items = ftp_nlist($this->conn, $fullPath);
        if ($items !== false) {
            foreach ($items as $item) {
                if (basename($item) === '.' || basename($item) === '..') continue;
                
                // ftp_nlist behavior varies (full path vs basename). 
                // We'll normalize to full path.
                $itemFullPath = str_contains($item, '/') ? $item : $fullPath . '/' . $item;

                $raw = @ftp_rawlist($this->conn, $itemFullPath);
                if ($raw !== false && !str_starts_with($raw[0], '-')) {
                    $this->deleteRecursive($itemFullPath);
                } else {
                    @ftp_delete($this->conn, $itemFullPath);
                }
            }
        }
        return @ftp_rmdir($this->conn, $fullPath);
    }

    public function createDirectory(string $path): bool
    {
        return (bool)@ftp_mkdir($this->conn, $this->resolvePath($path));
    }

    public function rename(string $from, string $to): bool
    {
        return ftp_rename($this->conn, $this->resolvePath($from), $this->resolvePath($to));
    }

    public function move(string $from, string $to): bool
    {
        return $this->rename($from, $to);
    }

    public function copy(string $from, string $to): bool
    {
        // FTP doesn't have a native copy. Must download and re-upload.
        $content = $this->readFile($from);
        return $this->writeFile($to, $content);
    }

    public function getMetadata(string $path): ?array
    {
        // Very inefficient on FTP but needed for interface
        $dir = dirname($path);
        $name = basename($path);
        $items = $this->listDirectory($dir === '.' ? '' : $dir);
        foreach ($items as $item) {
            if ($item['name'] === $name) return $item;
        }
        return null;
    }

    public function chmod(string $path, int $mode, bool $recursive = false): bool
    {
        if ($recursive) throw new Exception("Recursive chmod not supported on FTP");
        return (bool)ftp_chmod($this->conn, $mode, $this->resolvePath($path));
    }

    public function chown(string $path, $user, $group, bool $recursive = false): bool
    {
        // Most FTP servers don't support chown via standard commands.
        throw new Exception("FTP chown not supported");
    }

    public function archive(array $sources, string $destination): bool { throw new Exception("Archive not supported on FTP"); }
    public function extract(string $archive, string $destination): bool { throw new Exception("Extract not supported on FTP"); }
    public function search(string $query): array { throw new Exception("Search not supported on FTP"); }
    public function getDirectorySize(string $path): int { return 0; }
}
