<?php

declare(strict_types=1);

namespace App\Services\Dav;

use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\FS\Directory;

/**
 * Sabre's stock FS nodes follow symlinks. WebDAV must stay inside the
 * authenticated user's home, so every node lookup is checked against the
 * original real directory and symlinks are rejected.
 */
final class SafeDirectory extends Directory
{
    private string $jailRoot;

    public function __construct($path, ?string $jailRoot = null, $overrideName = null)
    {
        parent::__construct($path, $overrideName);
        $this->jailRoot = rtrim(realpath($jailRoot ?? $path) ?: $jailRoot ?? $path, '/\\');
        $this->assertPath($path);
    }

    public function getChild($name)
    {
        $path = $this->path . '/' . $name;
        $this->assertPath($path);

        if (!file_exists($path)) {
            throw new DAV\Exception\NotFound('WebDAV node was not found.');
        }

        if (is_dir($path)) {
            return new self($path, $this->jailRoot);
        }

        return new SafeFile($path, $this->jailRoot);
    }

    public function createFile($name, $data = null)
    {
        $this->assertChildTarget((string)$name);
        return parent::createFile($name, $data);
    }

    public function createDirectory($name)
    {
        $this->assertChildTarget((string)$name);
        return parent::createDirectory($name);
    }

    public function setName($name)
    {
        $this->assertChildName((string)$name);
        $destination = dirname($this->path) . DIRECTORY_SEPARATOR . $name;
        $this->assertChildTarget($name, $destination);
        parent::setName($name);
    }

    public function childExists($name)
    {
        $path = $this->path . '/' . $name;
        try {
            $this->assertPath($path);
        } catch (Forbidden) {
            return false;
        }

        return file_exists($path);
    }

    private function assertPath(string $path): void
    {
        if (is_link($path)) {
            throw new Forbidden('Symbolic links are not available through WebDAV.');
        }

        $realPath = realpath($path);
        if ($realPath === false || !$this->withinJail($realPath)) {
            throw new Forbidden('WebDAV path is outside the user home.');
        }
    }

    private function assertChildTarget(string $name, ?string $path = null): void
    {
        $this->assertChildName($name);
        $path ??= $this->path . DIRECTORY_SEPARATOR . $name;
        if (is_link($path)) {
            throw new Forbidden('Symbolic links are not available through WebDAV.');
        }
        if (file_exists($path)) {
            $this->assertPath($path);
            return;
        }
        $parent = realpath(dirname($path));
        if ($parent === false || !$this->withinJail($parent)) {
            throw new Forbidden('WebDAV path is outside the user home.');
        }
    }

    private function assertChildName(string $name): void
    {
        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, "\0")
            || str_contains($name, '/') || str_contains($name, '\\')) {
            throw new Forbidden('Invalid WebDAV node name.');
        }
    }

    private function withinJail(string $path): bool
    {
        return $path === $this->jailRoot
            || str_starts_with($path, $this->jailRoot . DIRECTORY_SEPARATOR);
    }
}
