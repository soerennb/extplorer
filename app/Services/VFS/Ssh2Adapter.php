<?php

namespace App\Services\VFS;

use App\Services\FileNamePolicy;
use App\Services\ResourcePolicy;
use App\Services\RemoteEndpointPolicy;
use App\Services\RemoteSecurityPolicy;
use phpseclib4\Crypt\PublicKeyLoader;
use phpseclib4\Crypt\Common\PrivateKey;
use phpseclib4\Exception\FileSystemException;
use phpseclib4\Net\SFTP;
use phpseclib4\Net\SFTP\StatusCode;
use RuntimeException;

/** SFTP transport with negotiated host-key verification and atomic stream activation. */
class Ssh2Adapter implements IFileSystem, StreamWritableFileSystem
{
    private SFTP $sftp;
    private string $root;
    private ResourcePolicy $policy;

    public function __construct(string $host, string $user, string $pass, int $port = 22, string $root = '/',
        string $hostKeyFingerprint = '', string $privateKey = '', string $publicKey = '', string $privateKeyPassphrase = '')
    {
        $this->policy = new ResourcePolicy();
        $this->root = RemotePathPolicy::normalizeRoot($root);
        $endpoint = (new RemoteEndpointPolicy())->authorize('sftp', $host, $port, $hostKeyFingerprint);
        $this->sftp = new SFTP($endpoint['connect_host'], $port, $this->policy->remoteTimeoutSeconds());
        $this->sftp->setTimeout($this->policy->remoteTimeoutSeconds());
        $this->sftp->disableStatCache();
        $hostKey = $this->sftp->getServerPublicHostKey();
        if (!is_string($hostKey)) throw new RuntimeException('Unable to obtain the negotiated SFTP host key.');
        (new RemoteSecurityPolicy())->assertSshHostKey($hostKey, $hostKeyFingerprint);
        $credential = $pass;
        if ($privateKey !== '' || $publicKey !== '') {
            if ($privateKey === '' || $publicKey === '') throw new RuntimeException('SFTP private and public keys are required.');
            $key = PublicKeyLoader::load($privateKey, $privateKeyPassphrase === '' ? null : $privateKeyPassphrase);
            if (!$key instanceof PrivateKey) throw new RuntimeException('SFTP private key is invalid.');
            $expected = PublicKeyLoader::loadPublicKey($publicKey)->toString('OpenSSH');
            if ($key->getPublicKey()->toString('OpenSSH') !== $expected) throw new RuntimeException('SFTP public and private keys do not match.');
            $credential = $key;
        }
        if (!$this->sftp->login($user, $credential)) throw new RuntimeException('SSH authentication failed.');
    }

    private function remotePath(string $path): string
    {
        return rtrim($this->root, '/') . '/' . RemotePathPolicy::normalizeRelative($path);
    }

    public function listDirectory(string $path, bool $showHidden = true): array
    {
        $items = $this->sftp->rawlist($this->remotePath($path));
        $this->policy->assertDirectoryEntries(count($items));
        $budget = $this->policy->startOperation();
        $results = [];
        foreach ($items as $name => $stat) {
            $budget->tick();
            if ($name === '.' || $name === '..' || (!$showHidden && str_starts_with($name, '.'))) continue;
            $relative = trim($path, '/') === '' ? $name : trim($path, '/') . '/' . $name;
            $results[] = $this->metadata($relative, $stat);
        }
        return $results;
    }

    public function getMetadata(string $path): ?array
    {
        try {
            return $this->metadata($path, $this->sftp->lstat($this->remotePath($path)));
        } catch (FileSystemException $error) {
            if (in_array($error->getCode(), [StatusCode::NO_SUCH_FILE, StatusCode::NO_SUCH_PATH], true)) return null;
            throw $error;
        }
    }

    private function metadata(string $path, array $stat): array
    {
        $directory = (($stat['mode'] ?? 0) & 0170000) === 0040000;
        return ['name' => basename($path), 'path' => $path, 'type' => $directory ? 'dir' : 'file',
            'size' => $stat['size'] ?? 0, 'mtime' => $stat['mtime'] ?? 0,
            'perms' => substr(sprintf('%o', $stat['mode'] ?? 0), -4), 'owner' => $stat['uid'] ?? 'unknown',
            'group' => $stat['gid'] ?? 'unknown', 'extension' => $directory ? null : pathinfo($path, PATHINFO_EXTENSION),
            'mime' => $directory ? 'directory' : 'application/octet-stream'];
    }

    public function readFile(string $path): string
    {
        $stream = $this->readStream($path, $this->policy->maxContentBytes());
        try { return $this->policy->readStream($stream); }
        finally { fclose($stream); }
    }

    public function openReadStream(string $path) { return $this->readStream($path, $this->policy->maxDownloadBytes()); }

    private function readStream(string $path, int $limit)
    {
        $metadata = $this->getMetadata($path);
        if ($metadata === null || $metadata['type'] !== 'file') throw new RuntimeException('SFTP source is not a file.');
        if ($metadata['size'] > $limit) throw new RuntimeException('Remote file exceeds the configured read limit.');
        $stream = tmpfile();
        if ($stream === false) throw new RuntimeException('Unable to allocate SFTP read staging.');
        try {
            $this->sftp->get($this->remotePath($path), $stream, 0, $limit + 1);
            $size = ftell($stream);
            if ($size === false || $size > $limit) throw new RuntimeException('Remote file exceeds the configured read limit.');
            rewind($stream);
            return $stream;
        } catch (\Throwable $error) { fclose($stream); throw $error; }
    }

    public function writeFile(string $path, string $content): bool
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) throw new RuntimeException('Unable to allocate content stream.');
        try {
            if (fwrite($stream, $content) !== strlen($content)) throw new RuntimeException('Unable to buffer content.');
            rewind($stream);
            return $this->writeStream($path, $stream);
        } finally { fclose($stream); }
    }

    public function writeStream(string $path, $source): bool
    {
        $this->assertWritableTarget($path);
        $directory = dirname(RemotePathPolicy::normalizeRelative($path));
        $temporary = ($directory === '.' ? '' : $directory . '/') . '.extplorer-write-' . bin2hex(random_bytes(16));
        $buffer = tmpfile();
        if ($buffer === false) throw new RuntimeException('Unable to allocate SFTP write staging.');
        try {
            $this->policy->copyStream($source, $buffer);
            rewind($buffer);
            try {
                $this->sftp->put($this->remotePath($temporary), $buffer);
                // Unsupported atomic overwrite is a retryable failure, never a delete-before-rename fallback.
                $this->sftp->posix_rename($this->remotePath($temporary), $this->remotePath($path));
            } catch (\Throwable $error) {
                try { $this->sftp->delete($this->remotePath($temporary), false); }
                catch (\Throwable $cleanupError) {
                    log_message('warning', 'Unable to remove failed SFTP staging file: {error}', ['error' => $cleanupError->getMessage()]);
                }
                throw $error;
            }
            return true;
        } finally {
            fclose($buffer);
        }
    }

    public function createDirectory(string $path): bool
    {
        $this->assertWritableTarget($path);
        $this->sftp->mkdir($this->remotePath($path), 0755, true);
        return true;
    }

    public function delete(string $path): bool
    {
        $budget = $this->policy->startOperation();
        return $this->deleteTree($path, $budget);
    }

    private function deleteTree(string $path, \App\Services\OperationBudget $budget): bool
    {
        $budget->tick();
        $metadata = $this->getMetadata($path);
        if ($metadata === null) return true;
        if ($metadata['type'] !== 'dir') {
            $this->sftp->delete($this->remotePath($path), false);
            return true;
        }
        foreach ($this->listDirectory($path) as $item) if (!$this->deleteTree($item['path'], $budget)) return false;
        $this->sftp->rmdir($this->remotePath($path));
        return true;
    }

    public function rename(string $from, string $to): bool
    {
        $this->assertWritableTarget($to);
        $this->sftp->rename($this->remotePath($from), $this->remotePath($to));
        return true;
    }

    public function move(string $from, string $to): bool { return $this->rename($from, $to); }

    public function copy(string $from, string $to): bool
    {
        $source = RemotePathPolicy::normalizeRelative($from);
        $target = RemotePathPolicy::normalizeRelative($to);
        if ($source === $target || ($source !== '' && str_starts_with($target, $source . '/'))) throw new RuntimeException('Cannot copy a directory into itself.');
        return $this->copyTree($source, $target, $this->policy->startOperation());
    }

    private function copyTree(string $from, string $to, \App\Services\OperationBudget $budget): bool
    {
        $budget->tick();
        $metadata = $this->getMetadata($from);
        if ($metadata === null) throw new RuntimeException('SFTP source was not found.');
        if ($metadata['type'] === 'dir') {
            if ($this->getMetadata($to) === null && !$this->createDirectory($to)) return false;
            foreach ($this->listDirectory($from) as $item) if (!$this->copyTree($item['path'], $to . '/' . $item['name'], $budget)) return false;
            return true;
        }
        $stream = $this->openReadStream($from);
        try { return $this->writeStream($to, $stream); }
        finally { fclose($stream); }
    }

    private function assertWritableTarget(string $path): void
    {
        (new FileNamePolicy())->assertSafePath(RemotePathPolicy::normalizeRelative($path));
        if (trim($path, '/') === '') throw new RuntimeException('A target filename is required.');
    }

    public function chmod(string $path, int $mode, bool $recursive = false): bool
    {
        if ($recursive) throw new RuntimeException('Recursive chmod is unavailable on SFTP.');
        $this->sftp->chmod($this->remotePath($path), $mode);
        return true;
    }

    public function chown(string $path, $user, $group, bool $recursive = false): bool { throw new RuntimeException('SFTP chown is unavailable.'); }
    public function archive(array $sources, string $destination): bool { throw new RuntimeException('Archives are unavailable on SFTP.'); }
    public function extract(string $archive, string $destination): bool { throw new RuntimeException('Archives are unavailable on SFTP.'); }
    public function search(string $query): array { throw new RuntimeException('Search is unavailable on SFTP.'); }
    public function getDirectorySize(string $path): int { return 0; }
}
