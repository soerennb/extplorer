<?php

namespace App\Services;

/** Application-managed files are data and must never select an executable. */
final class SendmailPolicy
{
    public const DEFAULT_PATH = '/usr/sbin/sendmail';

    public function configuredPath(): string
    {
        $path = getenv('EXTPLORER_SENDMAIL_PATH');
        return $path === false || trim($path) === '' ? self::DEFAULT_PATH : trim($path);
    }

    public function resolve(array $settings): string
    {
        $operatorPath = getenv('EXTPLORER_SENDMAIL_PATH');
        if (($operatorPath === false || trim($operatorPath) === '')
            && ($settings['sendmail_path'] ?? self::DEFAULT_PATH) !== self::DEFAULT_PATH) {
            throw new MailConfigurationException('sendmail_migration_required');
        }
        $path = $this->configuredPath();
        if (DIRECTORY_SEPARATOR !== '/' || !function_exists('posix_geteuid')
            || !str_starts_with($path, '/') || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new MailConfigurationException('sendmail_unavailable');
        }
        clearstatcache(true);
        $seen = [];
        $this->assertTrustedComponents($path, $seen);
        $real = realpath($path);
        if ($real === false || !is_file($real) || !is_executable($real)) {
            throw new MailConfigurationException('sendmail_unavailable');
        }
        $storage = config('Storage');
        foreach (get_object_vars($storage) as $root) {
            if (!is_string($root) || !str_starts_with($root, '/')) continue;
            $root = realpath($root);
            if ($root !== false && ($real === $root || str_starts_with($real, rtrim($root, '/') . '/'))) {
                throw new MailConfigurationException('sendmail_unavailable');
            }
        }
        return $real;
    }

    /** Include every intermediate symlink target, not just realpath's endpoint. */
    private function assertTrustedComponents(string $path, array &$seen, int $depth = 0): void
    {
        if ($depth > 40) throw new MailConfigurationException('sendmail_unavailable');
        $prefix = '';
        foreach (explode('/', $path) as $part) {
            if ($part === '') continue;
            $prefix .= '/' . $part;
            $stat = @lstat($prefix);
            if ($stat === false || $stat['uid'] === posix_geteuid()) {
                // Owners can chmod read-only files back to writable.
                throw new MailConfigurationException('sendmail_unavailable');
            }
            if (is_link($prefix)) {
                if (isset($seen[$prefix])) throw new MailConfigurationException('sendmail_unavailable');
                $seen[$prefix] = true;
                $target = readlink($prefix);
                if ($target === false) throw new MailConfigurationException('sendmail_unavailable');
                $this->assertTrustedComponents(str_starts_with($target, '/') ? $target : dirname($prefix) . '/' . $target, $seen, $depth + 1);
                unset($seen[$prefix]);
            } elseif (is_writable($prefix) || ($stat['mode'] & 0022) !== 0) {
                throw new MailConfigurationException('sendmail_unavailable');
            }
        }
    }
}
