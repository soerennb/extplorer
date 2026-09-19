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

    /** @return list<array{id:string,label:string,created_at:int,last_used_at:int|null}> */
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

    /** @return array{credential:array{id:string,label:string,created_at:int,last_used_at:int|null},secret:string} */
    public function create(string $username, string $label): array
    {
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

    /** @return array{id:string,label:string,created_at:int,last_used_at:int|null} */
    private function publicView(array $entry): array
    {
        return [
            'id' => (string)$entry['id'],
            'label' => (string)$entry['label'],
            'created_at' => (int)$entry['created_at'],
            'last_used_at' => isset($entry['last_used_at']) ? (int)$entry['last_used_at'] : null,
        ];
    }
}
