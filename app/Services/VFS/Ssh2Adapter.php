<?php

namespace App\Services\VFS;

use Exception;

class Ssh2Adapter implements IFileSystem
{
    private $conn;
    private $sftp;
    private string $root;

    public function __construct(string $host, string $user, string $pass, int $port = 22, string $root = '/')
    {
        if (!function_exists('ssh2_connect')) {
            throw new Exception("SSH2 extension not installed");
        }

        $this->conn = ssh2_connect($host, $port);
        if (!$this->conn) throw new Exception("Could not connect to SSH host: $host");

        if (!@ssh2_auth_password($this->conn, $user, $pass)) {
            throw new Exception("SSH Authentication failed for user: $user");
        }

        $this->sftp = ssh2_sftp($this->conn);
        if (!$this->sftp) throw new Exception("Could not initialize SFTP subsystem");

        $this->root = '/' . trim($root, '/');
    }

    private function resolvePath(string $path): string
    {
        return 'ssh2.sftp://' . intval($this->sftp) . $this->root . ($this->root === '/' ? '' : '/') . trim($path, '/');
    }

    public function listDirectory(string $path, bool $showHidden = true): array
    {
        $fullPath = $this->resolvePath($path);
        $handle = opendir($fullPath);
        if (!$handle) return [];

        $results = [];
        while (false !== ($file = readdir($handle))) {
            if ($file === '.' || $file === '..') continue;
            if (!$showHidden && str_starts_with($file, '.')) continue;

            $itemPath = $fullPath . '/' . $file;
            $relPath = ($path === '' || $path === '/' ? '' : trim($path, '/') . '/') . $file;
            
            $stat = ssh2_sftp_stat($this->sftp, $this->root . ($this->root === '/' ? '' : '/') . trim($relPath, '/'));
            $isDir = ($stat['mode'] & 040000) === 040000;

            $results[] = [
                'name' => $file,
                'path' => $relPath,
                'type' => $isDir ? 'dir' : 'file',
                'size' => $stat['size'] ?? 0,
                'mtime' => $stat['mtime'] ?? 0,
                'perms' => substr(sprintf('%o', $stat['mode']), -4),
                'owner' => $stat['uid'] ?? 'unknown',
                'group' => $stat['gid'] ?? 'unknown',
                'extension' => $isDir ? null : pathinfo($file, PATHINFO_EXTENSION),
                'mime' => $isDir ? 'directory' : 'application/octet-stream'
            ];
        }
        closedir($handle);
        return $results;
    }

    public function readFile(string $path): string
    {
        return file_get_contents($this->resolvePath($path));
    }

    public function writeFile(string $path, string $content): bool
    {
        return file_put_contents($this->resolvePath($path), $content) !== false;
    }

    public function delete(string $path): bool
    {
        $full = $this->resolvePath($path);
        
        if (is_dir($full)) {
            return $this->deleteRecursive($path);
        }
        return ssh2_sftp_unlink($this->sftp, $this->root . '/' . trim($path, '/'));
    }

    private function deleteRecursive(string $relPath): bool
    {
        $items = $this->listDirectory($relPath);
        foreach ($items as $item) {
            if ($item['type'] === 'dir') {
                $this->deleteRecursive($item['path']);
            } else {
                ssh2_sftp_unlink($this->sftp, $this->root . '/' . trim($item['path'], '/'));
            }
        }
        return ssh2_sftp_rmdir($this->sftp, $this->root . '/' . trim($relPath, '/'));
    }

    public function createDirectory(string $path): bool
    {
        return ssh2_sftp_mkdir($this->sftp, $this->root . '/' . trim($path, '/'), 0755, true);
    }

    public function rename(string $from, string $to): bool
    {
        return ssh2_sftp_rename($this->sftp, $this->root . '/' . trim($from, '/'), $this->root . '/' . trim($to, '/'));
    }

    public function move(string $from, string $to): bool
    {
        return $this->rename($from, $to);
    }

    public function copy(string $from, string $to): bool
    {
        $content = $this->readFile($from);
        return $this->writeFile($to, $content);
    }

    public function getMetadata(string $path): ?array
    {
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
        if ($recursive) throw new Exception("Recursive chmod not supported on SFTP");
        return ssh2_sftp_chmod($this->sftp, $this->root . '/' . trim($path, '/'), $mode);
    }

    public function chown(string $path, $user, $group, bool $recursive = false): bool
    {
        throw new Exception("SFTP chown not implemented via SSH2 extension wrapper");
    }

    public function archive(array $sources, string $destination): bool { throw new Exception("Not supported"); }
    public function extract(string $archive, string $destination): bool { throw new Exception("Not supported"); }
    public function search(string $query): array { throw new Exception("Not supported"); }
    public function getDirectorySize(string $path): int { return 0; }
}
