<?php

namespace App\Services\VFS;

use RuntimeException;

/** Frozen, validated namespace reference; credentials live in a separate private store. */
final class UploadTarget
{
    public function __construct(
        public readonly string $owner,
        public readonly string $kind,
        public readonly string $path,
        public readonly string $identity,
        public readonly string $fingerprint,
        public readonly int $authVersion,
    ) {
        if ($owner === '' || !in_array($kind, ['local', 'mount', 'remote'], true)
            || preg_match('/\A[a-f0-9]{64}\z/', $fingerprint) !== 1 || $authVersion < 0) throw new RuntimeException('Invalid upload target reference.');
        if (PathPolicy::normalizeRelative($path) !== $path || $path === '') throw new RuntimeException('Invalid upload target path.');
        if ($kind === 'remote' && preg_match('/\A[a-f0-9]{32}\z/', $identity) !== 1) throw new RuntimeException('Invalid remote upload reference.');
        if ($kind === 'mount' && ($identity === '' || preg_match('/\A[A-Za-z0-9_-]+\z/', $identity) !== 1)) throw new RuntimeException('Invalid mount upload reference.');
    }

    public function toArray(): array
    {
        return ['version' => 1, 'owner' => $this->owner, 'kind' => $this->kind, 'path' => $this->path,
            'identity' => $this->identity, 'fingerprint' => $this->fingerprint, 'auth_version' => $this->authVersion];
    }

    public static function fromArray(array $data): self
    {
        if (($data['version'] ?? null) !== 1 || !is_int($data['auth_version'] ?? null)) throw new RuntimeException('Invalid upload target version.');
        foreach (['owner', 'kind', 'path', 'identity', 'fingerprint'] as $field) {
            if (!is_string($data[$field] ?? null)) throw new RuntimeException('Invalid upload target field.');
        }
        return new self($data['owner'], $data['kind'], $data['path'], $data['identity'], $data['fingerprint'], $data['auth_version']);
    }
}
