<?php

namespace App\Controllers;

use App\Services\DownloadHeaders;
use App\Services\LogService;
use App\Services\ResourcePolicy;
use Exception;

trait ApiDownloadOperationsTrait
{
    public function download()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        $inline = $this->request->getGet('inline');

        if (!$path) return $this->fail('Path required');

        try {
            $policy = new ResourcePolicy();
            $fullPath = null;
            try {
                $candidate = $this->fs->resolvePath($path);
                if (is_file($candidate) || is_dir($candidate)) {
                    $fullPath = $candidate;
                }
            } catch (\Throwable $e) {
                // Remote adapters expose content through the VFS rather than
                // a local physical path.
            }

            $metadata = $this->fs->getMetadata($path);
            $isDirectory = ($metadata['type'] ?? null) === 'dir' || ($fullPath !== null && is_dir($fullPath));
            if ($isDirectory) {
                // Folder Download -> Zip
                $folderName = basename(trim(str_replace('\\', '/', $path), '/')) ?: 'files';
                $zipName = $folderName . '.zip';
                $tempZip = config('Storage')->cache . '/dl_' . bin2hex(random_bytes(16)) . '.zip';
                (new \App\Services\VfsArchiveService())->createZip($this->fs, [$path], $tempZip);
                register_shutdown_function(static function () use ($tempZip): void {
                    @unlink($tempZip);
                });
                return $this->response
                    ->download($tempZip, null)
                    ->setFileName(DownloadHeaders::filename($zipName))
                    ->setHeader('Content-Type', 'application/zip');
            }

            if (!is_array($metadata)) {
                return $this->failNotFound('File not found');
            }
            if ((int)($metadata['size'] ?? 0) > $policy->maxDownloadBytes()) {
                return $this->fail('File exceeds the configured download size limit.', 413);
            }

            // Security: Only allow inline for safe media types
            $filename = (string)($metadata['name'] ?? basename(trim(str_replace('\\', '/', $path), '/')));
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $safeInlineTypes = [
                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
                'pdf',
                'mp4', 'webm', 'ogv',
                'mp3', 'wav', 'ogg'
            ];

            if ($inline && !in_array($ext, $safeInlineTypes)) {
                $inline = false;
            }

            if ($inline) {
                $mime = $fullPath !== null ? (mime_content_type($fullPath) ?: 'application/octet-stream') : ((string)($metadata['mime'] ?? 'application/octet-stream'));
                if ($fullPath !== null) {
                    return $this->response
                        ->download($fullPath, null)
                        ->setFileName(DownloadHeaders::filename($filename))
                        ->setHeader('Content-Type', $mime)
                        ->setHeader('Content-Disposition', DownloadHeaders::contentDisposition('inline', $filename));
                }

                $temporary = $this->stageStream($path, (int)($metadata['size'] ?? 0));
                return $this->response
                    ->download($temporary, null)
                    ->setFileName(DownloadHeaders::filename($filename))
                    ->setHeader('Content-Type', $mime)
                    ->setHeader('Content-Disposition', DownloadHeaders::contentDisposition('inline', $filename));
            }

            if ($fullPath !== null) {
                return $this->response
                    ->download($fullPath, null)
                    ->setFileName(DownloadHeaders::filename($filename));
            }

            $temporary = $this->stageStream($path, (int)($metadata['size'] ?? 0));
            return $this->response
                ->download($temporary, null)
                ->setFileName(DownloadHeaders::filename($filename))
                ->setHeader('Content-Type', (string)($metadata['mime'] ?? 'application/octet-stream'))
                ->setHeader('Content-Disposition', DownloadHeaders::contentDisposition('attachment', $filename));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function thumb()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');

        try {
            $fullPath = $this->fs->resolvePath($path);
            if (!is_file($fullPath)) return $this->failNotFound();

            $sourceSize = filesize($fullPath);
            $policy = new ResourcePolicy();
            if ($sourceSize === false || $sourceSize > $policy->maxImageBytes()) {
                return $this->fail('Image is too large to generate a thumbnail.', 413);
            }
            $dimensions = @getimagesize($fullPath);
            if (!is_array($dimensions) || ((int)($dimensions[0] ?? 0) * (int)($dimensions[1] ?? 0)) > $policy->maxImagePixels()) {
                return $this->fail('Image dimensions exceed the configured safety limit.', 413);
            }

            // Cache file path (hash of full path + mtime to invalidate on change)
            $mtime = filemtime($fullPath);
            if ($mtime === false) {
                return $this->fail('Unable to inspect image.', 500);
            }
            $cacheName = md5($fullPath . $mtime) . '.jpg';
            $cacheDir = config('Storage')->cache . '/thumbs';
            if (!is_dir($cacheDir) && !mkdir($cacheDir, 0750, true) && !is_dir($cacheDir)) {
                return $this->fail('Unable to create thumbnail cache.', 500);
            }
            $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $cacheName;

            // Generate if not exists
            if (!file_exists($cachePath)) {
                $image = \Config\Services::image();
                try {
                    if (!$image->withFile($fullPath)
                        ->fit(100, 100, 'center')
                        ->save($cachePath, 80)) {
                        return $this->fail('Unable to generate thumbnail.', 500);
                    }
                } catch (\CodeIgniter\Images\Exceptions\ImageException $e) {
                    // If not an image or processing fails, return a default placeholder or 404
                    // For simplicity, let's just fail, frontend will handle broken img
                    return $this->fail('Not an image');
                }
            }

            // Serve
            $this->response->setHeader('Content-Type', 'image/jpeg');
            $thumbnail = file_get_contents($cachePath);
            if ($thumbnail === false) {
                return $this->fail('Unable to read thumbnail.', 500);
            }
            $this->response->setBody($thumbnail);
            return $this->response;

        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function search()
    {
        if (!can('read')) return $this->failForbidden();
        $query = $this->request->getGet('q');
        if (!$query) return $this->fail('Query required');

        try {
            $results = $this->fs->search($query);
            return $this->respond([
                'items' => $results,
                'total' => count($results)
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function dirsize()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');

        try {
            $size = $this->fs->getDirectorySize($path);
            return $this->respond(['size' => $size]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function archive()
    {
        if (!can('archive')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $paths = $json->paths ?? [];
        $name = $json->name ?? 'archive.zip';
        $cwd = $json->cwd ?? '';

        if (empty($paths)) return $this->fail('No files selected');

        try {
            // Prepend CWD to paths if necessary, assuming paths are relative to CWD
            // Actually, let's assume the frontend sends full relative paths from root,
            // OR relative to CWD. Let's enforce relative to root for clarity in API,
            // but store.js currently handles CWD.
            // Let's assume `paths` are full relative paths (e.g. "folder/file.txt")

            $destination = ($cwd ? $cwd . '/' : '') . $name;
            if (!$this->fs->archive($paths, $destination)) {
                throw new Exception('Unable to create archive.');
            }
            LogService::log('Archive', $destination, 'Sources: ' . count($paths));
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function extract()
    {
        if (!can('extract')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $cwd = $json->cwd ?? '';

        if (!$path) return $this->fail('Path required');

        try {
            // Extract to current folder
            if (!$this->fs->extract($path, $cwd ?: '/')) {
                throw new Exception('Unable to extract archive.');
            }
            LogService::log('Extract', $path, 'To: ' . ($cwd ?: '/'));
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

        public function chmod()

        {

            if (!can('chmod')) return $this->failForbidden();

            $json = $this->request->getJSON();

            $paths = $json->paths ?? [$json->path ?? null];

            $mode = $json->mode ?? null;

            $recursive = (bool)($json->recursive ?? false);



            if (empty(array_filter($paths)) || !$mode) return $this->fail('Paths and Mode required');



            try {

                $octalMode = intval(strval($mode), 8);

                foreach ($paths as $path) {

                    if ($path && !$this->fs->chmod($path, $octalMode, $recursive)) {
                        throw new Exception('Unable to change permissions.');
                    }

                }

                LogService::log('Chmod' . ($recursive ? ' (Recursive)' : ''), implode(', ', $paths), 'Mode: ' . $mode);

                return $this->respond(['status' => 'success']);

            } catch (\Throwable $e) {

                return $this->fail($e->getMessage());

            }

        }

    private function stageStream(string $path, int $declaredSize = 0): string
    {
        $policy = new ResourcePolicy();
        if ($declaredSize > $policy->maxDownloadBytes()) {
            throw new Exception('Download exceeds the configured size limit.');
        }

        $directory = config('Storage')->runtime;
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new Exception('Unable to create download staging directory.');
        }
        $temporary = tempnam($directory, '.extplorer-download-');
        if ($temporary === false) {
            throw new Exception('Unable to create download staging file.');
        }

        $source = null;
        $target = null;
        try {
            $source = $this->fs->openReadStream($path);
            $target = fopen($temporary, 'wb');
            if ($target === false) {
                throw new Exception('Unable to open download staging file.');
            }
            $copied = $policy->copyStream($source, $target);
            if ($declaredSize > 0 && $copied !== $declaredSize) {
                throw new Exception('File changed while it was being downloaded.');
            }
        } catch (\Throwable $exception) {
            @unlink($temporary);
            throw $exception;
        } finally {
            if (is_resource($source)) fclose($source);
            if (is_resource($target)) fclose($target);
        }

        register_shutdown_function(static function () use ($temporary): void {
            @unlink($temporary);
        });
        return $temporary;
    }
}
