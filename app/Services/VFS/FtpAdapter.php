<?php

namespace App\Services\VFS;

use App\Services\FileNamePolicy;
use App\Services\RemoteEndpointPolicy;
use App\Services\ResourcePolicy;
use Exception;

class FtpAdapter implements IFileSystem, StreamWritableFileSystem
{
    private $conn;
    private string $root;
    private string $host;
    private int $port;
    private bool $secure;

    public function __construct(
        string $host,
        string $user,
        string $pass,
        int $port = 21,
        string $root = '/',
        bool $secure = false,
        string $tlsSpkiPin = ''
    )
    {
        if (!$secure && !function_exists('ftp_connect')) {
            throw new Exception("FTP extension not installed");
        }
        if ($secure && !function_exists('ftp_ssl_connect')) {
            throw new Exception("FTPS is not available on this server");
        }
        $this->host = $host;
        $this->port = $port;
        $this->secure = $secure;
        $this->root = RemotePathPolicy::normalizeRoot($root);

        $endpoint = (new RemoteEndpointPolicy())->authorize($secure ? 'ftps' : 'ftp', $host, $port, '', $secure);
        if ($secure) {
            (new \App\Services\RemoteSecurityPolicy())->verifyFtpsCertificate(
                $endpoint['host'],
                $endpoint['connect_host'],
                $port,
                ['spki_pin' => $tlsSpkiPin]
            );
        }
        $this->host = $endpoint['host'];

        $connectHost = $endpoint['connect_host'];
        $timeout = (new ResourcePolicy())->remoteTimeoutSeconds();
        $this->conn = $secure
            ? ftp_ssl_connect($connectHost, $port, $timeout)
            : ftp_connect($connectHost, $port, $timeout);
        if (!$this->conn) throw new Exception("Could not connect to FTP host: $host");

        if (defined('FTP_TIMEOUT_SEC')) {
            ftp_set_option($this->conn, FTP_TIMEOUT_SEC, $timeout);
        }
        // An allowlisted FTP server must not redirect the data connection to
        // loopback, metadata services or another unapproved PASV address.
        if (!ftp_set_option($this->conn, FTP_USEPASVADDRESS, false)) {
            throw new Exception(lang('Remote.passive_address_denied'));
        }

        if (!@ftp_login($this->conn, $user, $pass)) {
            throw new Exception("FTP Login failed for user: $user");
        }

        if (!ftp_pasv($this->conn, true)) {
            throw new Exception('Could not enable passive FTP mode.');
        }
    }

    public function __destruct()
    {
        if ($this->conn) {
            try { @ftp_close($this->conn); } catch (\Throwable) {}
        }
    }

    private function resolvePath(string $path): string
    {
        return $this->remotePath($path);
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    private function remotePath(string $path): string
    {
        $relative = RemotePathPolicy::normalizeRelative($path);
        return $this->root . ($this->root === '/' ? '' : '/') . $relative;
    }

    public function listDirectory(string $path, bool $showHidden = true): array
    {
        $budget = (new ResourcePolicy())->startOperation();
        $fullPath = $this->resolvePath($path);
        $raw = ftp_rawlist($this->conn, $fullPath);
        if ($raw === false) return [];
        (new ResourcePolicy())->assertDirectoryEntries(count($raw));

        $results = [];
        foreach ($raw as $line) {
            $budget->tick();
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
        $policy = new ResourcePolicy();
        $stream = $this->download($path, $policy->maxContentBytes());
        try { return $policy->readStream($stream); }
        finally { fclose($stream); }
    }

    public function openReadStream(string $path)
    {
        return $this->download($path, (new ResourcePolicy())->maxDownloadBytes());
    }

    private function download(string $path, int $limit)
    {
        $this->assertRemoteSize($path, $limit);
        $temp = tmpfile();
        if ($temp === false) throw new Exception('Unable to allocate FTP download stream.');
        try {
            if (!ftp_fget($this->conn, $temp, $this->resolvePath($path), FTP_BINARY)) {
                throw new Exception("Could not open FTP file: {$path}");
            }
            $stats = fstat($temp);
            if ($stats === false || (int)$stats['size'] > $limit) {
                throw new Exception('Remote file exceeds the configured resource limit.');
            }
            rewind($temp);
            return $temp;
        } catch (\Throwable $error) {
            fclose($temp);
            throw $error;
        }
    }

    public function writeFile(string $path, string $content): bool
    {
        $source = fopen('php://temp', 'w+b');
        if ($source === false) throw new Exception('Unable to allocate content stream.');
        try {
            if (fwrite($source, $content) !== strlen($content)) throw new Exception('Unable to buffer content.');
            rewind($source);
            return $this->writeStream($path, $source);
        } finally { fclose($source); }
    }

    public function writeStream(string $path, $source): bool
    {
        $this->assertWritableTarget($path);
        $relative = RemotePathPolicy::normalizeRelative($path);
        $directory = dirname($relative);
        $temporary = ($directory === '.' ? '' : $directory . '/') . '.extplorer-write-' . bin2hex(random_bytes(16));
        $buffer = tmpfile();
        if ($buffer === false) throw new Exception('Unable to stage remote write.');
        try {
            (new ResourcePolicy())->copyStream($source, $buffer);
            rewind($buffer);
            if (!ftp_fput($this->conn, $this->remotePath($temporary), $buffer, FTP_BINARY)) throw new Exception('Unable to write remote staging file.');
            if (!ftp_rename($this->conn, $this->remotePath($temporary), $this->remotePath($relative))) throw new Exception('Remote server could not activate the staged file.');
            return true;
        } finally {
            fclose($buffer);
            @ftp_delete($this->conn, $this->remotePath($temporary));
        }
    }

    public function delete(string $path): bool
    {
        return $this->deleteTree($path, (new ResourcePolicy())->startOperation());
    }

    private function deleteTree(string $path, \App\Services\OperationBudget $budget): bool
    {
        $budget->tick();
        $metadata = $this->getMetadata($path);
        if ($metadata === null) return false;
        if ($metadata['type'] !== 'dir') return ftp_delete($this->conn, $this->remotePath($path));
        foreach ($this->listDirectory($path) as $item) {
            if (!$this->deleteTree($item['path'], $budget)) return false;
        }
        return ftp_rmdir($this->conn, $this->remotePath($path));
    }

    public function createDirectory(string $path): bool
    {
        $this->assertWritableTarget($path);
        return (bool)@ftp_mkdir($this->conn, $this->resolvePath($path));
    }

    public function rename(string $from, string $to): bool
    {
        $this->assertWritableTarget($to);
        return ftp_rename($this->conn, $this->remotePath($from), $this->remotePath($to));
    }

    public function move(string $from, string $to): bool
    {
        return $this->rename($from, $to);
    }

    public function copy(string $from, string $to): bool
    {
        $this->assertWritableTarget($to);
        $this->assertValidCopyTarget($from, $to);
        return $this->copyTree($from, $to, (new ResourcePolicy())->startOperation());
    }

    private function copyTree(string $from, string $to, \App\Services\OperationBudget $budget): bool
    {
        $budget->tick();
        $meta = $this->getMetadata($from);
        if ($meta === null) throw new Exception("Source not found: $from");
        if ($meta['type'] !== 'dir') {
            $stream = $this->openReadStream($from);
            try { return $this->writeStream($to, $stream); }
            finally { fclose($stream); }
        }
        if (!$this->createDirectory($to)) {
            $existing = $this->getMetadata($to);
            if ($existing === null || $existing['type'] !== 'dir') {
                throw new Exception("Could not create destination directory: $to");
            }
        }
        foreach ($this->listDirectory($from) as $item) {
            if (!$this->copyTree($item['path'], $this->joinRelativePath($to, $item['name']), $budget)) return false;
        }
        return true;
    }

    private function assertValidCopyTarget(string $from, string $to): void
    {
        $source = $this->normalizeRelativePath($from);
        $target = $this->normalizeRelativePath($to);

        if ($source === $target) {
            throw new Exception('Cannot copy a directory onto itself.');
        }

        if ($source !== '' && str_starts_with($target, $source . '/')) {
            throw new Exception('Cannot copy a directory into one of its descendants.');
        }
    }

    private function joinRelativePath(string $base, string $name): string
    {
        $base = trim($base, '/');
        return $base === '' ? $name : $base . '/' . $name;
    }

    private function normalizeRelativePath(string $path): string
    {
        return RemotePathPolicy::normalizeRelative($path);
    }

    private function assertRemoteSize(string $path, int $limit): void
    {
        $size = @ftp_size($this->conn, $this->resolvePath($path));
        if ($size >= 0 && $size > $limit) {
            throw new Exception('Remote file exceeds the configured resource limit.');
        }
    }

    private function assertWritableTarget(string $path): void
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        if ($normalized === '') {
            throw new Exception('A target filename is required.');
        }
        (new FileNamePolicy())->assertSafePath($normalized);
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
        return (bool)ftp_chmod($this->conn, $mode, $this->remotePath($path));
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
