<?php

namespace App\Services;

use RuntimeException;

/** Bounded encrypted leases for direct-login uploads that await an external scan. */
final class RemoteUploadCredentials
{
    private string $path;

    public function __construct()
    {
        $this->path = config('Storage')->state . '/remote-upload-connections.php';
    }

    public static function fingerprint(array $connection): string
    {
        ksort($connection);
        return hash('sha256', json_encode($connection, JSON_THROW_ON_ERROR));
    }

    public function capture(string $owner, array $connection): string
    {
        $id = bin2hex(random_bytes(16));
        $ciphertext = (new RemoteCredentialService())->protect(json_encode($connection, JSON_THROW_ON_ERROR));
        AtomicFileStore::transaction($this->path, function (array &$leases) use ($id, $owner, $ciphertext, $connection): void {
            $this->expire($leases);
            if (count($leases) >= (new ResourcePolicy())->maxUploadStagingFiles()) throw new RuntimeException('Remote upload credential capacity is exhausted.');
            $leases[$id] = ['owner' => $owner, 'sealed_connection' => $ciphertext, 'fingerprint' => self::fingerprint($connection),
                'expires_at' => time() + (new ResourcePolicy())->configuredInteger('EXTPLORER_UPLOAD_QUARANTINE_TTL_SECONDS', 86400, 60, 604800)];
        });
        return $id;
    }

    public function resolve(string $id, string $owner, string $fingerprint): array
    {
        $lease = AtomicFileStore::read($this->path)[$id] ?? null;
        if (!is_array($lease) || ($lease['owner'] ?? '') !== $owner || (int)($lease['expires_at'] ?? 0) <= time()
            || !hash_equals((string)($lease['fingerprint'] ?? ''), $fingerprint)) throw new RuntimeException('Remote upload connection expired or changed.');
        $plain = (new RemoteCredentialService())->reveal((string)$lease['sealed_connection']);
        $connection = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($connection) || !hash_equals(self::fingerprint($connection), $fingerprint)) throw new RuntimeException('Remote upload connection is invalid.');
        return $connection;
    }

    public function release(string $id): void
    {
        AtomicFileStore::transaction($this->path, function (array &$leases) use ($id): void { unset($leases[$id]); $this->expire($leases); });
    }

    public function cleanupExpired(): void
    {
        AtomicFileStore::transaction($this->path, function (array &$leases): void { $this->expire($leases); });
    }

    private function expire(array &$leases): void
    {
        foreach ($leases as $id => $lease) if ((int)($lease['expires_at'] ?? 0) <= time()) unset($leases[$id]);
    }
}
