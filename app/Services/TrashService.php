<?php

namespace App\Services;

use Exception;

class TrashService
{
    private string $trashRoot;
    private string $username;
    private object $storage;

    public function __construct(string $username)
    {
        if (preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{2,63}\z/', trim($username)) !== 1) {
            throw new Exception('Invalid trash owner.');
        }
        $this->username = $username;
        $this->storage = config('Storage');
        // Trash root per user: writable/trash/{username}
        $this->trashRoot = $this->storage->trash . DIRECTORY_SEPARATOR . $username;

        $this->assertNoSymlinkComponents($this->trashRoot);
        if (!is_dir($this->trashRoot)) {
            mkdir($this->trashRoot, 0755, true);
        }
        if (is_link($this->trashRoot) || !is_dir($this->trashRoot)) {
            throw new Exception('Trash storage is not safe.');
        }
        if (is_link($this->getIndexFile())) {
            throw new Exception('Trash index is not safe.');
        }

        // Ensure index file exists. Legacy conversion is performed centrally by
        // DataMigrationService before the application is marked ready.
        if (!file_exists($this->getIndexFile())) {
            $this->saveIndex([]);
        }
    }

    private function getIndexFile(): string
    {
        return $this->trashRoot . DIRECTORY_SEPARATOR . 'index.php';
    }

    private function getIndex(): array
    {
        if (!file_exists($this->getIndexFile())) return [];
        return AtomicFileStore::read($this->getIndexFile());
    }

    private function saveIndex(array $index): void
    {
        AtomicFileStore::write($this->getIndexFile(), $index);
    }

    /**
     * Moves a file or directory to the trash.
     * 
     * @param string $fullPath The absolute path to the file/dir to delete.
     * @param string $relativePath The relative path (for display/restore).
     */
    public function moveToTrash(string $fullPath, string $relativePath): void
    {
        if (!file_exists($fullPath) || is_link($fullPath)) {
            throw new Exception("File not found: $fullPath");
        }

        $realPath = realpath($fullPath);
        if ($realPath === false) {
            throw new Exception('Trash source is not a valid filesystem path.');
        }
        foreach ([$this->storage->root, $this->storage->fileManagerRoot, $this->storage->shared] as $protectedRoot) {
            $protected = realpath((string)$protectedRoot);
            if ($protected !== false && $realPath === $protected) {
                throw new Exception('Cannot move a managed root to trash.');
            }
        }

        $id = uniqid();
        $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
        if (is_link($trashPath)) {
            throw new Exception('Trash destination is not safe.');
        }
        
        // Move the actual file/folder
        if (!rename($fullPath, $trashPath)) {
            throw new Exception("Failed to move item to trash.");
        }

        try {
            AtomicFileStore::transaction($this->getIndexFile(), function (array &$index) use ($id, $relativePath, $trashPath): void {
                $index[$id] = [
                    'id' => $id,
                    'originalPath' => $relativePath,
                    'name' => basename($relativePath),
                    'deletedAt' => time(),
                    'type' => is_dir($trashPath) ? 'dir' : 'file',
                    'size' => is_dir($trashPath) ? 0 : filesize($trashPath),
                ];
            });
        } catch (\Throwable $exception) {
            rename($trashPath, $fullPath);
            throw $exception;
        }
    }

    /**
     * Restores an item from the trash to its original location.
     */
    public function restore(string $id, \App\Services\VFS\IFileSystem $fs): void
    {
        $this->assertId($id);
        AtomicFileStore::transaction($this->getIndexFile(), function (array &$index) use ($id, $fs): void {
            if (!isset($index[$id])) {
                throw new Exception("Item not found in trash index.");
            }

            $item = $index[$id];
            $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
            if (is_link($trashPath)) {
                throw new Exception('Trash item is not a regular file or directory.');
            }
            $originalPath = (string)($item['originalPath'] ?? '');
            (new FileNamePolicy())->assertSafe(basename(str_replace('\\', '/', $originalPath)));
            $targetPath = $fs->resolvePath($originalPath);

            if (file_exists($targetPath)) {
                $info = pathinfo($targetPath);
                $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
                $base = $info['filename'];
                $targetPath = $info['dirname'] . DIRECTORY_SEPARATOR . $base . ' (Restored)' . $ext;
            }

            $parentDir = dirname($targetPath);
            $this->assertNoSymlinkComponents($parentDir);
            if (!is_dir($parentDir) && !mkdir($parentDir, 0755, true) && !is_dir($parentDir)) {
                throw new Exception("Failed to create restore directory.");
            }
            $this->assertNoSymlinkComponents($parentDir);

            if (!rename($trashPath, $targetPath)) {
                throw new Exception("Failed to restore item.");
            }

            unset($index[$id]);
        });
    }

    /**
     * Permanently deletes an item from the trash.
     */
    public function deletePermanently(string $id): void
    {
        $this->assertId($id);
        AtomicFileStore::transaction($this->getIndexFile(), function (array &$index) use ($id): void {
            $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
            if (file_exists($trashPath)) {
                $this->recursiveDelete($trashPath);
            }
            unset($index[$id]);
        });
    }

    /**
     * Empties the user's trash.
     */
    public function emptyTrash(): void
    {
        AtomicFileStore::transaction($this->getIndexFile(), function (array &$index): void {
            foreach ($index as $id => $item) {
                $this->assertId((string)$id);
                $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
                if (file_exists($trashPath)) {
                    $this->recursiveDelete($trashPath);
                }
            }
            $index = [];
        });
    }

    /**
     * Lists all items in the trash.
     */
    public function listItems(): array
    {
        return array_values($this->getIndex());
    }

    private function recursiveDelete(string $path): void
    {
        if (is_link($path)) {
            unlink($path);
            return;
        }
        if (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                $this->recursiveDelete($path . DIRECTORY_SEPARATOR . $file);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }

    private function assertId(string $id): void
    {
        if (preg_match('/\A[a-f0-9]{13,64}\z/i', $id) !== 1) {
            throw new Exception('Invalid trash item ID.');
        }
    }

    private function assertNoSymlinkComponents(string $path): void
    {
        $current = rtrim($path, '/\\');
        while ($current !== dirname($current)) {
            if (is_link($current)) {
                throw new Exception('Trash storage uses a symbolic link.');
            }
            $current = dirname($current);
        }
    }
}
