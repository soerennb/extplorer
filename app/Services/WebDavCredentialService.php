<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

final class WebDavCredentialService
{
    private const MAX_PER_USER = 10;
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? (new \Config\Storage())->state . '/webdav_credentials.php';
    }

    /** @return list<array{id:string,label:string,created_at:int,last_used_at:int|null,expires_at:int|null}> */
    public function listForUser(string $username): array
    {
        $result = [];
        foreach (AtomicFileStore::read($this->path) as $entry) {
            if (!is_array($entry) || !hash_equals((string)($entry['username'] ?? ''), $username)) {
                continue;
            }
            $result[] = $this->publicView($entry);
        }
        return $result;
    }

    /** @return array{credential:array{id:string,label:string,created_at:int,last_used_at:int|null,expires_at:int|null},secret:string} */
    public function create(string $username, string $label): array
    {
        $user = (new \App\Models\UserModel())->getUser($username);
        $external = ($user['auth_source'] ?? 'local') === 'external';
        $policy = $external ? \App\Services\ExternalAuth\ExternalWebDavPolicy::forUser($user) : null;
        if ($external && (!$policy['enabled'] || !session('isLoggedIn') || session('username') !== $username
            || !\App\Services\ExternalAuth\ExternalSessionPolicy::isValid($user))) {
            throw new RuntimeException(lang('ExternalAuth.managed'));
        }
        $label = trim($label);
        if ($label === '' || mb_strlen($label) > 64) {
            throw new InvalidArgumentException('Credential label must contain 1 to 64 characters.');
        }

        $id = bin2hex(random_bytes(16));
        $secret = 'ex3dav_' . $id . '_' . bin2hex(random_bytes(32));
        $entry = [
            'id' => $id,
            'username' => $username,
            'label' => $label,
            'password_hash' => password_hash($secret, PASSWORD_DEFAULT),
            'created_at' => time(),
            'last_used_at' => null,
        ];
        if ($external) {
            $entry += ['expires_at' => time() + $policy['ttl'], 'external_identity' => $user['external_identity'],
                'auth_version' => (int)$user['auth_version'], 'provider_fingerprint' => $policy['fingerprint']];
            \App\Services\ExternalAuth\ExternalState::transaction(static function (array &$documents) use ($username, $entry, $user): void {
                $current = null;
                foreach ($documents['users'] as $candidate) if ($candidate['username'] === $username) $current = $candidate;
                if (!$current || !\App\Services\ExternalAuth\ExternalState::accountAllowed($current, $documents['external_auth'])
                    || (int)$current['auth_version'] !== (int)$user['auth_version']) throw new RuntimeException('External account changed.');
                $entries = &$documents['webdav_credentials'];
                $entries = array_values(array_filter($entries, static fn(array $item): bool => ($item['expires_at'] ?? PHP_INT_MAX) > time()));
                if (count(array_filter($entries, static fn(array $item): bool => $item['username'] === $username)) >= self::MAX_PER_USER) {
                    throw new RuntimeException('A maximum of ten WebDAV credentials is allowed.');
                }
                $entries[] = $entry;
            });
            return ['credential' => $this->publicView($entry), 'secret' => $secret];
        }
        AtomicFileStore::transaction($this->path, function (array &$entries) use ($username, $entry): void {
            $count = count(array_filter($entries, static fn ($item): bool =>
                is_array($item) && hash_equals((string)($item['username'] ?? ''), $username)
            ));
            if ($count >= self::MAX_PER_USER) {
                throw new RuntimeException('A maximum of ten WebDAV credentials is allowed.');
            }
            $entries[] = $entry;
        });

        return ['credential' => $this->publicView($entry), 'secret' => $secret];
    }

    public function verify(string $username, string $secret): bool
    {
        if (preg_match('/\Aex3dav_([a-f0-9]{32})_[a-f0-9]{64}\z/', $secret, $matches) !== 1) {
            return false;
        }
        $id = $matches[1];
        $user = (new \App\Models\UserModel())->getUser($username);
        if (($user['auth_source'] ?? 'local') === 'external') return $this->verifyExternal($user, $secret, $id);

        return AtomicFileStore::transaction($this->path, function (array &$entries) use ($username, $secret, $id): bool {
            foreach ($entries as &$entry) {
                if (!is_array($entry)
                    || !hash_equals((string)($entry['username'] ?? ''), $username)
                    || !hash_equals((string)($entry['id'] ?? ''), $id)
                    || !password_verify($secret, (string)($entry['password_hash'] ?? ''))
                ) {
                    continue;
                }
                $entry['last_used_at'] = time();
                return true;
            }
            unset($entry);
            return false;
        });
    }

    private function verifyExternal(array $user, string $secret, string $id): bool
    {
        $policy = \App\Services\ExternalAuth\ExternalWebDavPolicy::forUser($user);
        return \App\Services\ExternalAuth\ExternalState::transaction(static function (array &$documents) use ($user, $secret, $id, $policy): bool {
            $current = null;
            foreach ($documents['users'] as $candidate) if ($candidate['username'] === $user['username']) $current = $candidate;
            foreach ($documents['webdav_credentials'] as $index => &$entry) {
                if (($entry['id'] ?? '') !== $id || ($entry['username'] ?? '') !== $user['username']) continue;
                if (!$current || !$policy['enabled'] || !\App\Services\ExternalAuth\ExternalState::accountAllowed($current, $documents['external_auth'])
                    || ($entry['external_identity'] ?? null) !== $current['external_identity']
                    || ($entry['auth_version'] ?? null) !== (int)$current['auth_version']
                    || ($entry['provider_fingerprint'] ?? null) !== $policy['fingerprint']
                    || ($entry['expires_at'] ?? 0) <= time()) {
                    unset($documents['webdav_credentials'][$index]);
                    $documents['webdav_credentials'] = array_values($documents['webdav_credentials']);
                    return false;
                }
                if (!password_verify($secret, $entry['password_hash'])) return false;
                $entry['last_used_at'] = time();
                return true;
            }
            return false;
        });
    }

    public function delete(string $username, string $id): bool
    {
        return AtomicFileStore::transaction($this->path, function (array &$entries) use ($username, $id): bool {
            $before = count($entries);
            $entries = array_values(array_filter($entries, static fn ($entry): bool =>
                !is_array($entry)
                || !hash_equals((string)($entry['username'] ?? ''), $username)
                || !hash_equals((string)($entry['id'] ?? ''), $id)
            ));
            return count($entries) !== $before;
        });
    }

    public function revokeUser(string $username): void
    {
        AtomicFileStore::transaction($this->path, static function (array &$entries) use ($username): void {
            $entries = array_values(array_filter($entries, static fn ($entry): bool =>
                !is_array($entry) || !hash_equals((string)($entry['username'] ?? ''), $username)
            ));
        });
    }

    /** @return array{id:string,label:string,created_at:int,last_used_at:int|null,expires_at:int|null} */
    private function publicView(array $entry): array
    {
        return [
            'id' => (string)$entry['id'],
            'label' => (string)$entry['label'],
            'created_at' => (int)$entry['created_at'],
            'last_used_at' => isset($entry['last_used_at']) ? (int)$entry['last_used_at'] : null,
            'expires_at' => $entry['expires_at'] ?? null,
        ];
    }
}
