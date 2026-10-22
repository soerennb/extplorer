<?php

namespace App\Services;

use App\Models\UserModel;
use RuntimeException;

/**
 * The account's allowed/blocked extension policy for every operation that
 * introduces a filename: uploads, saves, renames, copies, archives and
 * extraction. The hard FileNamePolicy deny-list always applies as well.
 */
final class AccountFilePolicy
{
    public function __construct(private string $allowed = '', private string $blocked = '') {}

    /** Persisted policy of the signed-in local account; direct remote logins have none. */
    public static function forCurrentSession(): self
    {
        $username = SessionAccount::localUsername();
        if ($username === null) {
            return new self();
        }

        $user = (new UserModel())->getUser($username);
        if ($user === null) {
            throw new RuntimeException('Authenticated user no longer exists.');
        }

        return new self((string)($user['allowed_extensions'] ?? ''), (string)($user['blocked_extensions'] ?? ''));
    }

    public function allows(string $filename): bool
    {
        return (new FileNamePolicy())->isAllowed($filename, $this->allowed, $this->blocked);
    }

    public function assertFile(string $path): void
    {
        $filename = basename(str_replace('\\', '/', trim($path, '/\\')));
        if (!$this->allows($filename)) {
            throw new RuntimeException(lang('Files.type_not_allowed'));
        }
    }
}
