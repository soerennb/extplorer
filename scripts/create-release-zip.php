<?php

/** Portable archive fallback when the host has PHP ZipArchive but no zip executable. */
if ($argc !== 3 || !is_dir($argv[1])) {
    fwrite(STDERR, "Usage: php create-release-zip.php <directory> <archive.zip>\n");
    exit(1);
}
$root = realpath($argv[1]);
$archive = new ZipArchive();
if ($archive->open($argv[2], ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create release ZIP.');
}
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($files as $file) {
    if ($file->isLink()) continue;
    $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root) + 1));
    if ($file->isDir()) {
        if (!$archive->addEmptyDir($relative)) throw new RuntimeException('Unable to package release directory: ' . $relative);
        continue;
    }
    if (!$archive->addFile($file->getPathname(), $relative)) throw new RuntimeException('Unable to package release file: ' . $relative);
}
if (!$archive->close()) throw new RuntimeException('Unable to finish release ZIP.');
