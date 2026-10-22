<?php

namespace App\Services;

use App\Models\UserModel;
use App\Services\VFS\IFileSystem;
use App\Services\VFS\PathPolicy;
use App\Services\VFS\UploadTarget;
use App\Services\VFS\VfsFactory;
use RuntimeException;

/** Revalidates frozen upload namespaces under current account and endpoint policies. */
class UploadTargetResolver
{
    public function capture(string $owner, array $connection, string $path): UploadTarget
    {
        $path = PathPolicy::normalizeRelative($path);
        (new FileNamePolicy())->assertSafePath($path);
        if (($connection['mode'] ?? 'local') !== 'local') {
            $fingerprint = RemoteUploadCredentials::fingerprint($connection);
            $reference = (new RemoteUploadCredentials())->capture($owner, $connection);
            return new UploadTarget($owner, 'remote', $path, $reference, $fingerprint, 0);
        }
        $user = $this->authorizedUser($owner);
        [$alias, $relative] = array_pad(explode('/', $path, 2), 2, '');
        if (in_array($alias, ['Home', 'Shared', 'Public'], true)) {
            $fingerprint = $this->localFingerprint($alias, $user);
            return new UploadTarget($owner, 'local', $path, $alias, $fingerprint, (int)$user['auth_version']);
        }
        foreach ((new MountService())->getUserMounts($owner) as $mount) {
            if (($mount['name'] ?? '') === $alias) return new UploadTarget($owner, 'mount', $relative, (string)$mount['id'], $this->mountFingerprint($mount), (int)$user['auth_version']);
        }
        throw new RuntimeException('Upload storage was not found.');
    }

    /** @return array{0: IFileSystem, 1: string} */
    public function resolve(UploadTarget $target): array
    {
        if ($target->kind === 'remote') {
            $connection = (new RemoteUploadCredentials())->resolve($target->identity, $target->owner, $target->fingerprint);
            if (!empty($connection['direct_login'])) (new RemoteEndpointPolicy())->assertDirectLoginEnabled();
            return [VfsFactory::createAdapter((string)$connection['mode'], $connection), $target->path];
        }
        $user = $this->authorizedUser($target->owner);
        if ((int)$user['auth_version'] !== $target->authVersion) throw new RuntimeException('Upload account authorization changed.');
        if (!(new FileNamePolicy())->isAllowed(basename($target->path), $user['allowed_extensions'] ?? '', $user['blocked_extensions'] ?? '')) throw new RuntimeException('File type is no longer allowed.');
        if ($target->kind === 'mount') {
            $mount = (new MountService())->getMountForUser($target->identity, $target->owner, true);
            if (!hash_equals($target->fingerprint, $this->mountFingerprint($mount))) throw new RuntimeException('Upload mount namespace changed.');
            return [VfsFactory::createMountAdapter($mount), $target->path];
        }
        if (!hash_equals($target->fingerprint, $this->localFingerprint($target->identity, $user))) throw new RuntimeException('Upload storage namespace changed.');
        [$alias, $relative] = array_pad(explode('/', $target->path, 2), 2, '');
        if ($alias !== $target->identity) throw new RuntimeException('Upload storage namespace changed.');
        return [VfsFactory::createLocalAdapter($alias, $user), $relative];
    }

    public function assertContext(UploadTarget $target, string $owner, array $connection): void
    {
        if ($target->owner !== $owner) throw new RuntimeException('Upload target does not belong to this user.');
        if ($target->kind === 'remote') {
            if (!hash_equals($target->fingerprint, RemoteUploadCredentials::fingerprint($connection))) throw new RuntimeException('Upload connection changed.');
        } elseif (($connection['mode'] ?? 'local') !== 'local') {
            throw new RuntimeException('Upload connection changed.');
        }
    }

    public function release(UploadTarget $target): void
    {
        if ($target->kind === 'remote') (new RemoteUploadCredentials())->release($target->identity);
    }

    private function authorizedUser(string $owner): array
    {
        $snapshot = (new UserModel())->authorizationSnapshot($owner);
        $user = $snapshot['user'] ?? null;
        $permissions = $snapshot['permissions'] ?? [];
        if ($user === null || !empty($user['disabled'])
            || (!in_array('*', $permissions, true) && !in_array('upload', $permissions, true))) throw new RuntimeException('Upload account is no longer authorized.');
        return $user;
    }

    private function localFingerprint(string $alias, array $user): string
    {
        return hash('sha256', VfsFactory::localRoot($alias, $user));
    }

    private function mountFingerprint(array $mount): string
    {
        $config = array_intersect_key($mount['config'], array_flip(['path', 'host', 'port', 'root', 'user', 'host_key_fingerprint', 'tls_spki_pin']));
        ksort($config);
        return hash('sha256', json_encode([$mount['type'], $config], JSON_THROW_ON_ERROR));
    }
}
