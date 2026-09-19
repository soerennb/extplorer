<?php

namespace App\Services;

use RuntimeException;

/**
 * Keeps writable application storage outside the public document tree.
 */
final class StorageBoundaryPolicy
{
    /**
     * Validate all configured writable roots against the application webroot
     * and protected source directories.
     */
    public function assertSafe(?object $storage = null): void
    {
        $storage ??= config('Storage');
        $publicRoot = $this->canonicalPath(ROOTPATH . 'public');
        $protectedRoots = [
            ROOTPATH . 'app',
            ROOTPATH . 'system',
            ROOTPATH . 'vendor',
            ROOTPATH . 'tests',
        ];

        $paths = [
            'storage root' => $storage->root,
            'state' => $storage->state,
            'logs' => $storage->logs,
            'session' => $storage->session,
            'uploads' => $storage->uploads,
            'file manager root' => $storage->fileManagerRoot,
            'shared' => $storage->shared,
            'trash' => $storage->trash,
            'versions' => $storage->versions,
            'cache' => $storage->cache,
            'runtime' => $storage->runtime,
            'backups' => $storage->backups,
        ];

        foreach ($paths as $label => $path) {
            $canonical = $this->canonicalPath((string)$path);
            if ($this->overlaps($canonical, $publicRoot)) {
                throw new RuntimeException(
                    "Configured {$label} must be outside the public webroot: {$canonical}"
                );
            }
            foreach ($protectedRoots as $protectedRoot) {
                $protected = $this->canonicalPath($protectedRoot);
                if ($this->overlaps($canonical, $protected)) {
                    throw new RuntimeException(
                        "Configured {$label} must not overlap protected application files: {$canonical}"
                    );
                }
            }
        }

        $canonicalPaths = [];
        foreach ($paths as $label => $path) {
            $canonicalPaths[$label] = $this->canonicalPath((string)$path);
        }
        if (strcmp($canonicalPaths['file manager root'], $canonicalPaths['storage root']) === 0) {
            throw new RuntimeException('File manager root must not be the complete writable storage root.');
        }
        unset($canonicalPaths['storage root']);
        $labels = array_keys($canonicalPaths);
        for ($leftIndex = 0, $labelCount = count($labels); $leftIndex < $labelCount; $leftIndex++) {
            for ($rightIndex = $leftIndex + 1; $rightIndex < $labelCount; $rightIndex++) {
                $left = $labels[$leftIndex];
                $right = $labels[$rightIndex];
                if ($this->overlaps($canonicalPaths[$left], $canonicalPaths[$right])) {
                    throw new RuntimeException(
                        "Configured storage paths must not overlap: {$left} and {$right}"
                    );
                }
            }
        }
    }

    /**
     * Public for focused configuration tests and mount validation.
     */
    public function assertOutsideWebroot(string $path, string $publicRoot): string
    {
        $canonical = $this->canonicalPath($path);
        $public = $this->canonicalPath($publicRoot);
        if ($this->overlaps($canonical, $public)) {
            throw new RuntimeException("Writable path overlaps the public webroot: {$canonical}");
        }

        return $canonical;
    }

    private function canonicalPath(string $path): string
    {
        $path = rtrim(trim($path), '/\\');
        if ($path === '') {
            throw new RuntimeException('Configured storage path is empty.');
        }

        $missing = [];
        $current = $path;
        while (!file_exists($current)) {
            $parent = dirname($current);
            if ($parent === $current) {
                throw new RuntimeException("Unable to resolve configured storage path: {$path}");
            }
            $missing[] = basename($current);
            $current = $parent;
        }

        if (is_link($current)) {
            throw new RuntimeException("Configured storage path uses a symbolic link: {$path}");
        }

        $this->assertNoSymlinkComponents($path);

        $real = realpath($current);
        if ($real === false) {
            throw new RuntimeException("Unable to resolve configured storage path: {$path}");
        }

        foreach (array_reverse($missing) as $part) {
            $real .= DIRECTORY_SEPARATOR . $part;
        }

        return rtrim($real, '/\\');
    }

    private function assertNoSymlinkComponents(string $path): void
    {
        $current = rtrim($path, '/\\');
        while ($current !== dirname($current)) {
            if (is_link($current)) {
                throw new RuntimeException("Configured storage path uses a symbolic link: {$path}");
            }
            $current = dirname($current);
        }
    }

    private function overlaps(string $left, string $right): bool
    {
        $left = rtrim(str_replace('\\', '/', $left), '/') . '/';
        $right = rtrim(str_replace('\\', '/', $right), '/') . '/';
        if (DIRECTORY_SEPARATOR === '\\') {
            $left = strtolower($left);
            $right = strtolower($right);
        }

        return str_starts_with($left, $right) || str_starts_with($right, $left);
    }
}
