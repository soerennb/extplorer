<?php

namespace App\Services;

/**
 * Distinguishes local accounts from direct FTP/SFTP logins.
 *
 * A direct remote login authenticates an account on the remote endpoint. Its
 * name may coincide with a local account, so it must never be used to address
 * local state such as profiles, shares, transfers, trash or mounts.
 */
final class SessionAccount
{
    public static function connectionMode(): string
    {
        $connection = session('connection');
        return is_array($connection) ? (string)($connection['mode'] ?? 'local') : 'local';
    }

    public static function isLocal(): bool
    {
        return self::connectionMode() === 'local';
    }

    public static function localUsername(): ?string
    {
        $username = session('username');
        if (!self::isLocal() || !is_string($username) || $username === '') {
            return null;
        }

        return $username;
    }

    /** Name recorded in audit logs; remote identities are kept distinguishable from local accounts. */
    public static function auditName(): ?string
    {
        $username = session('username');
        if (!is_string($username) || $username === '') {
            return null;
        }
        if (self::isLocal()) {
            return $username;
        }

        $connection = (array)session('connection');
        return sprintf('remote:%s://%s@%s', self::connectionMode(), $username, (string)($connection['host'] ?? ''));
    }
}
