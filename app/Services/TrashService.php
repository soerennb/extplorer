<?php

namespace App\Services;

use Exception;

class TrashService
{
    private string $trashRoot;
    private string $username;

    public function __construct(string $username)
    {
        $this->username = $username;
        // Trash root per user: writable/trash/{username}
        $this->trashRoot = WRITEPATH . 'trash' . DIRECTORY_SEPARATOR . $username;

        if (!is_dir($this->trashRoot)) {
            mkdir($this->trashRoot, 0755, true);
        }

        // Ensure index file exists
        if (!file_exists($this->getIndexFile())) {
            file_put_contents($this->getIndexFile(), json_encode([]));
        }
    }

    private function getIndexFile(): string
    {
        return $this->trashRoot . DIRECTORY_SEPARATOR . 'index.json';
    }

    private function getIndex(): array
    {
        $content = file_get_contents($this->getIndexFile());
        return json_decode($content, true) ?? [];
    }

    private function saveIndex(array $index): void
    {
        file_put_contents($this->getIndexFile(), json_encode($index, JSON_PRETTY_PRINT));
    }

    /**
     * Moves a file or directory to the trash.
     * 
     * @param string $fullPath The absolute path to the file/dir to delete.
     * @param string $relativePath The relative path (for display/restore).
     */
    public function moveToTrash(string $fullPath, string $relativePath): void
    {
        if (!file_exists($fullPath)) {
            throw new Exception("File not found: $fullPath");
        }

        $id = uniqid();
        $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
        
        // Move the actual file/folder
        if (!rename($fullPath, $trashPath)) {
            throw new Exception("Failed to move item to trash.");
        }

        // Update Index
        $index = $this->getIndex();
        $index[$id] = [
            'id' => $id,
            'originalPath' => $relativePath,
            'name' => basename($relativePath),
            'deletedAt' => time(),
            'type' => is_dir($trashPath) ? 'dir' : 'file',
            'size' => is_dir($trashPath) ? 0 : filesize($trashPath) // Simple size
        ];
        
        $this->saveIndex($index);
    }

    /**
     * Restores an item from the trash to its original location.
     */
    public function restore(string $id, \App\Services\VFS\IFileSystem $fs): void
    {
        $index = $this->getIndex();
        if (!isset($index[$id])) {
            throw new Exception("Item not found in trash index.");
        }

        $item = $index[$id];
        $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
        
        // Resolve restoration path via VFS to ensure security/home-jail
        $targetPath = $fs->resolvePath($item['originalPath']);

        // Handle collision: if target exists, append " (Restored)"
        if (file_exists($targetPath)) {
            $info = pathinfo($targetPath);
            $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
            $base = $info['filename'];
            $targetPath = $info['dirname'] . DIRECTORY_SEPARATOR . $base . ' (Restored)' . $ext;
        }

        // Ensure parent directory exists
        $parentDir = dirname($targetPath);
        if (!is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        if (!rename($trashPath, $targetPath)) {
            throw new Exception("Failed to restore item.");
        }

        // Remove from index
        unset($index[$id]);
        $this->saveIndex($index);
    }

    /**
     * Permanently deletes an item from the trash.
     */
    public function deletePermanently(string $id): void
    {
        $index = $this->getIndex();
        if (!isset($index[$id])) {
            // Check if file exists even if index is missing (cleanup)
             $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
             if (file_exists($trashPath)) {
                 $this->recursiveDelete($trashPath);
             }
             return;
        }

        $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
        
        if (file_exists($trashPath)) {
            $this->recursiveDelete($trashPath);
        }

        unset($index[$id]);
        $this->saveIndex($index);
    }

    /**
     * Empties the user's trash.
     */
    public function emptyTrash(): void
    {
        $index = $this->getIndex();
        foreach ($index as $id => $item) {
            $trashPath = $this->trashRoot . DIRECTORY_SEPARATOR . $id;
            if (file_exists($trashPath)) {
                $this->recursiveDelete($trashPath);
            }
        }
        $this->saveIndex([]);
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
}
