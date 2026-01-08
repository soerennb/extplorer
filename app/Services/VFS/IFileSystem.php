<?php

namespace App\Services\VFS;

interface IFileSystem
{
    /**
     * List files and directories in the given path.
     *
     * @param string $path Relative path
     * @param bool $showHidden Whether to include hidden files
     * @return array List of files/folders with metadata
     */
    public function listDirectory(string $path, bool $showHidden = true): array;

    /**
     * Read file content.
     *
     * @param string $path Relative path
     * @return string File content
     */
    public function readFile(string $path): string;

    /**
     * Write content to a file.
     *
     * @param string $path Relative path
     * @param string $content Content to write
     * @return bool True on success
     */
    public function writeFile(string $path, string $content): bool;

    /**
     * Delete a file or directory (recursive).
     *
     * @param string $path Relative path
     * @return bool True on success
     */
    public function delete(string $path): bool;

    /**
     * Create a new directory.
     *
     * @param string $path Relative path
     * @return bool True on success
     */
    public function createDirectory(string $path): bool;

    /**
     * Rename a file or directory.
     *
     * @param string $from Source path (relative)
     * @param string $to Destination path (relative)
     * @return bool True on success
     */
    public function rename(string $from, string $to): bool;

    /**
     * Move a file or directory.
     *
     * @param string $from Source path (relative)
     * @param string $to Destination path (relative)
     * @return bool True on success
     */
    public function move(string $from, string $to): bool;

    /**
     * Copy a file or directory.
     *
     * @param string $from Source path (relative)
     * @param string $to Destination path (relative)
     * @return bool True on success
     */
    public function copy(string $from, string $to): bool;

    /**
     * Get detailed metadata for a file/directory.
     *
     * @param string $path Relative path
     * @return array|null Metadata array or null if not found
     */
    public function getMetadata(string $path): ?array;

    /**
     * Change file permissions.
     *
     * @param string $path Relative path
     * @param int $mode Octal mode (e.g. 0755)
     * @param bool $recursive Whether to apply to subdirectories
     * @return bool True on success
     */
    public function chmod(string $path, int $mode, bool $recursive = false): bool;

    /**
     * Change file owner/group.
     *
     * @param string $path Relative path
     * @param string|int $user User name or ID
     * @param string|int $group Group name or ID
     * @param bool $recursive Whether to apply to subdirectories
     * @return bool True on success
     */
    public function chown(string $path, $user, $group, bool $recursive = false): bool;

    /**
     * Calculate total size of a directory recursively.
     *
     * @param string $path Relative path
     * @return int Size in bytes
     */
    public function getDirectorySize(string $path): int;
}
