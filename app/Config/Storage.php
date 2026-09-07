<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Persistent storage locations used by eXtplorer.
 *
 * Keeping these paths in one place prevents application metadata from being
 * mixed with the file-manager root, sessions, or temporary upload data.
 */
class Storage extends BaseConfig
{
    public string $root;
    public string $state;
    public string $logs;
    public string $session;
    public string $uploads;
    public string $fileManagerRoot;
    public string $shared;
    public string $trash;
    public string $versions;
    public string $cache;
    public string $runtime;
    public string $backups;
    public string $stateDriver;
    public string $sessionDriver;
    public string $cacheDriver;

    public function __construct()
    {
        parent::__construct();

        $root = getenv('EXTPLORER_WRITE_PATH');
        if ($root === false || trim($root) === '') {
            $root = getenv('WRITEPATH');
        }
        if ($root === false || trim((string) $root) === '') {
            $root = __DIR__ . '/../../writable';
        }

        $this->root = rtrim((string) $root, '/\\');
        $this->state = $this->resolveDirectory('EXTPLORER_STATE_PATH', $this->root . '/config');
        $this->logs = $this->root . '/logs';
        $this->session = $this->root . '/session';
        $this->uploads = $this->root . '/uploads';
        $this->fileManagerRoot = $this->resolveDirectory(
            'EXTPLORER_FILE_MANAGER_ROOT',
            $this->root . '/file_manager_root'
        );
        $this->shared = $this->root . '/shared';
        $this->trash = $this->root . '/trash';
        $this->versions = $this->root . '/versions';
        $this->cache = $this->root . '/cache';
        $this->runtime = $this->root . '/runtime';
        $this->backups = $this->root . '/backups';

        $this->stateDriver = $this->driver('EXTPLORER_STATE_DRIVER', ['file', 'sqlite', 'database'], 'file');
        $this->sessionDriver = $this->driver('EXTPLORER_SESSION_DRIVER', ['file', 'database', 'redis'], 'file');
        $this->cacheDriver = $this->driver('EXTPLORER_CACHE_DRIVER', ['file', 'redis', 'dummy'], 'file');
    }

    private function resolveDirectory(string $environmentKey, string $default): string
    {
        $value = getenv($environmentKey);
        if ($value === false || trim($value) === '') {
            return rtrim($default, '/\\');
        }

        $value = rtrim(trim($value), '/\\');
        if (!$this->isAbsolutePath($value)) {
            throw new \RuntimeException("{$environmentKey} must be an absolute path.");
        }

        return $value;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || (bool) preg_match('/\A[A-Za-z]:[\\\\\/]/', $path);
    }

    /** @param list<string> $allowed */
    private function driver(string $key, array $allowed, string $default): string
    {
        $value = strtolower(trim((string)(getenv($key) ?: $default)));
        if (!in_array($value, $allowed, true)) {
            throw new \RuntimeException("{$key} must be one of: " . implode(', ', $allowed));
        }

        return $value;
    }
}
