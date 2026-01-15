<?php

namespace App\Services\VFS;

use Exception;

class VirtualAdapter implements IFileSystem
{
    /** @var array<string, IFileSystem> */
    private array $mounts = [];

    public function mount(string $alias, IFileSystem $adapter): void
    {
        $this->mounts[$alias] = $adapter;
    }

    private function resolveMount(string $path): array
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        $parts = explode('/', $path, 2);
        $alias = $parts[0];
        $remaining = $parts[1] ?? '';

        if (isset($this->mounts[$alias])) {
            return [$this->mounts[$alias], $remaining];
        }

        return [null, $path];
    }

    public function listDirectory(string $path, bool $showHidden = true): array
    {
        $path = trim($path, '/\\');
        
        if ($path === '') {
            // Root: List mounts
            $result = [];
            foreach ($this->mounts as $alias => $adapter) {
                $result[] = [
                    'name' => $alias,
                    'path' => $alias,
                    'type' => 'dir',
                    'size' => 0, // Could sum up, but expensive
                    'mtime' => time(),
                    'perms' => '0755',
                    'owner' => 'root',
                    'group' => 'root',
                    'mime' => 'directory',
                    'extension' => null
                ];
            }
            return $result;
        }

        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) throw new Exception("Path not found: $path");

        $items = $adapter->listDirectory($relPath, $showHidden);
        
        // Fix up paths to include the mount alias
        // $path here is full virtual path e.g. "Personal/subdir"
        // Adapter returns items relative to its root, but usually it echoes the input path in 'path' key.
        // e.g. LocalAdapter('subdir') -> returns 'subdir/file.txt'
        // We want 'Personal/subdir/file.txt'
        
        // Actually, if we passed $relPath ('subdir') to adapter, it returns 'subdir/file.txt'.
        // We need to prepend the mount alias.
        
        $alias = explode('/', $path, 2)[0];

        foreach ($items as &$item) {
            // If adapter returns paths starting with slash, trim it
            $p = ltrim($item['path'], '/');
            $item['path'] = $alias . '/' . $p;
        }
        
        return $items;
    }

    public function readFile(string $path): string
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) throw new Exception("Path not found: $path");
        return $adapter->readFile($relPath);
    }

    public function writeFile(string $path, string $content): bool
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) {
             if (trim($path, '/\\') === '') throw new Exception("Cannot write to Virtual Root. Please select a folder.");
             throw new Exception("Path not found: $path");
        }
        return $adapter->writeFile($relPath, $content);
    }

    public function delete(string $path): bool
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) throw new Exception("Path not found: $path");
        
        if ($relPath === '') {
            throw new Exception("Cannot delete a mount point directly");
        }
        
        return $adapter->delete($relPath);
    }

    public function createDirectory(string $path): bool
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) {
             if (trim($path, '/\\') === '') throw new Exception("Cannot create directory in Virtual Root.");
             throw new Exception("Path not found: $path");
        }
        return $adapter->createDirectory($relPath);
    }

    public function rename(string $from, string $to): bool
    {
        return $this->move($from, $to);
    }

    public function move(string $from, string $to): bool
    {
        [$adapterFrom, $pathFrom] = $this->resolveMount($from);
        [$adapterTo, $pathTo] = $this->resolveMount($to);

        if (!$adapterFrom) throw new Exception("Source path not found: $from");
        if (!$adapterTo) {
             if (trim($to, '/\\') === '') throw new Exception("Cannot move to Virtual Root.");
             throw new Exception("Destination path not found: $to");
        }

        if ($adapterFrom === $adapterTo) {
            return $adapterFrom->move($pathFrom, $pathTo);
        }

        // Cross-mount move
        // Check if directory to support recursive move
        $meta = $adapterFrom->getMetadata($pathFrom);
        if (!$meta) throw new Exception("Source not found");
        
        if ($meta['type'] === 'dir') {
            throw new Exception("Cross-mount directory move not implemented yet");
        }

        $content = $adapterFrom->readFile($pathFrom);
        if ($adapterTo->writeFile($pathTo, $content)) {
            return $adapterFrom->delete($pathFrom);
        }
        return false;
    }

    public function copy(string $from, string $to): bool
    {
        [$adapterFrom, $pathFrom] = $this->resolveMount($from);
        [$adapterTo, $pathTo] = $this->resolveMount($to);

        if (!$adapterFrom) throw new Exception("Source path not found: $from");
        if (!$adapterTo) {
             if (trim($to, '/\\') === '') throw new Exception("Cannot copy to Virtual Root.");
             throw new Exception("Destination path not found: $to");
        }

        if ($adapterFrom === $adapterTo) {
            return $adapterFrom->copy($pathFrom, $pathTo);
        }

        // Cross-mount copy
         $meta = $adapterFrom->getMetadata($pathFrom);
        if (!$meta) throw new Exception("Source not found");
        
        if ($meta['type'] === 'dir') {
            throw new Exception("Cross-mount directory copy not implemented yet");
        }

        $content = $adapterFrom->readFile($pathFrom);
        return $adapterTo->writeFile($pathTo, $content);
    }

    public function getMetadata(string $path): ?array
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) return null;
        
        if ($relPath === '') {
             // Metadata for the mount itself
             return [
                'name' => basename($path),
                'path' => $path,
                'type' => 'dir',
                'size' => 0,
                'mtime' => time(),
                'perms' => '0755',
                'owner' => 'root',
                'group' => 'root',
                'mime' => 'directory',
                'extension' => null
            ];
        }

        $meta = $adapter->getMetadata($relPath);
        if ($meta) {
            $alias = explode('/', $path, 2)[0];
            $meta['path'] = $alias . '/' . ltrim($meta['path'], '/');
        }
        return $meta;
    }

    public function chmod(string $path, int $mode, bool $recursive = false): bool
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) throw new Exception("Path not found: $path");
        return $adapter->chmod($relPath, $mode, $recursive);
    }

    public function chown(string $path, $user, $group, bool $recursive = false): bool
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) throw new Exception("Path not found: $path");
        return $adapter->chown($relPath, $user, $group, $recursive);
    }

    public function getDirectorySize(string $path): int
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if (!$adapter) return 0;
        return $adapter->getDirectorySize($relPath);
    }

    public function archive(array $sources, string $destination): bool
    {
        // Check if all sources are on the same mount as destination
        [$destAdapter, $destPath] = $this->resolveMount($destination);
        if (!$destAdapter) throw new Exception("Destination not found");

        $relSources = [];
        foreach ($sources as $src) {
            [$srcAdapter, $srcPath] = $this->resolveMount($src);
            if ($srcAdapter !== $destAdapter) {
                throw new Exception("Cross-mount archiving not supported");
            }
            $relSources[] = $srcPath;
        }

        return $destAdapter->archive($relSources, $destPath);
    }

    public function extract(string $archive, string $destination): bool
    {
        [$archAdapter, $archPath] = $this->resolveMount($archive);
        [$destAdapter, $destPath] = $this->resolveMount($destination);

        if ($archAdapter !== $destAdapter) {
            throw new Exception("Cross-mount extraction not supported");
        }

        return $archAdapter->extract($archPath, $destPath);
    }

    public function search(string $query): array
    {
        $allResults = [];
        foreach ($this->mounts as $alias => $adapter) {
            try {
                $results = $adapter->search($query);
                foreach ($results as $item) {
                    $item['path'] = $alias . '/' . ltrim($item['path'], '/');
                    $allResults[] = $item;
                }
            } catch (Exception $e) {
                // Ignore errors from specific mounts (e.g. FTP)
            }
        }
        return $allResults;
    }

    // Proxy for legacy LocalAdapter compatibility
    public function resolvePath(string $path): string
    {
        [$adapter, $relPath] = $this->resolveMount($path);
        if ($adapter && method_exists($adapter, 'resolvePath')) {
            return $adapter->resolvePath($relPath);
        }
        throw new Exception("Cannot resolve physical path for: $path");
    }
}
