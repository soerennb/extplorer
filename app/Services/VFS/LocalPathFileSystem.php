<?php

namespace App\Services\VFS;

interface LocalPathFileSystem
{
    /** Null means this target has no local filesystem path. */
    public function resolveLocalPath(string $path): ?string;
}
