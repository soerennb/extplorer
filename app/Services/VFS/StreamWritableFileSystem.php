<?php

namespace App\Services\VFS;

/** Additive capability; existing IFileSystem implementations remain valid. */
interface StreamWritableFileSystem
{
    /** @param resource $source Caller retains ownership; consume from its current position. */
    public function writeStream(string $path, $source): bool;
}
