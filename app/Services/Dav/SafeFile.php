<?php

declare(strict_types=1);

namespace App\Services\Dav;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\FS\File;

final class SafeFile extends File
{
    private string $jailRoot;

    public function __construct($path, string $jailRoot, $overrideName = null)
    {
        parent::__construct($path, $overrideName);
        $this->jailRoot = rtrim(realpath($jailRoot) ?: $jailRoot, '/\\');
        if (is_link($path) || ($realPath = realpath($path)) === false
            || !($realPath === $this->jailRoot || str_starts_with($realPath, $this->jailRoot . DIRECTORY_SEPARATOR))) {
            throw new Forbidden('WebDAV path is outside the user home.');
        }
    }

    public function put($data)
    {
        $this->assertCurrentPath();
        return parent::put($data);
    }

    public function setName($name)
    {
        $name = (string)$name;
        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, "\0")
            || str_contains($name, '/') || str_contains($name, '\\')) {
            throw new Forbidden('Invalid WebDAV node name.');
        }

        $destination = dirname($this->path) . DIRECTORY_SEPARATOR . $name;
        if (is_link($destination) || file_exists($destination)) {
            throw new Forbidden('WebDAV destination is not available.');
        }
        $parent = realpath(dirname($destination));
        if ($parent === false || !($parent === $this->jailRoot || str_starts_with($parent, $this->jailRoot . DIRECTORY_SEPARATOR))) {
            throw new Forbidden('WebDAV path is outside the user home.');
        }

        parent::setName($name);
        $this->assertCurrentPath();
    }

    private function assertCurrentPath(): void
    {
        if (is_link($this->path) || ($realPath = realpath($this->path)) === false
            || !($realPath === $this->jailRoot || str_starts_with($realPath, $this->jailRoot . DIRECTORY_SEPARATOR))) {
            throw new Forbidden('WebDAV path is outside the user home.');
        }
    }
}
