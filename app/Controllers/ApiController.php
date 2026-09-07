<?php

namespace App\Controllers;

use App\Services\VFS\LocalAdapter;
use App\Services\LogService;
use App\Services\UploadSessionService;
use App\Services\DownloadHeaders;
use Exception;

class ApiController extends BaseController
{
    use ApiResponseTrait;

    private \App\Services\VFS\IFileSystem $fs;

    public function __construct()
    {
        $conn = session('connection') ?? ['mode' => 'local'];
        $username = session('username');
        
        $this->fs = \App\Services\VFS\VfsFactory::createFileSystem($username, $conn);
    }

    public function ls()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path') ?? '';
        $showHidden = $this->request->getGet('showHidden') === 'true';
        $limit = (int) ($this->request->getGet('limit') ?? 0);
        $offset = (int) ($this->request->getGet('offset') ?? 0);
        $sortBy = $this->request->getGet('sortBy') ?? 'name';
        $sortDesc = $this->request->getGet('sortDesc') === 'true';
        $allowedSorts = ['name', 'size', 'mtime'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        try {
            $data = $this->fs->listDirectory($path, $showHidden);
            usort($data, function ($a, $b) use ($sortBy, $sortDesc) {
                if (($a['type'] ?? '') !== ($b['type'] ?? '')) {
                    return ($a['type'] ?? '') === 'dir' ? -1 : 1;
                }
                $valA = $a[$sortBy] ?? 0;
                $valB = $b[$sortBy] ?? 0;
                if ($sortBy === 'name') {
                    $cmp = strnatcasecmp((string) $valA, (string) $valB);
                } else {
                    $cmp = ($valA <=> $valB);
                }
                return $sortDesc ? -$cmp : $cmp;
            });
            $total = count($data);

            if ($limit > 0) {
                $data = array_slice($data, $offset, $limit);
            }

            // Inject Share Status
            try {
                $username = session('username');
                $shareService = new \App\Services\ShareService();
                $shares = $shareService->listUserShares($username);
                $shareMap = [];
                foreach ($shares as $s) $shareMap[$s['path']] = true;

                foreach ($data as &$item) {
                    if (isset($shareMap[$item['path']])) $item['is_shared'] = true;
                }
            } catch (\Exception $e) {
                // Ignore share service errors to not break ls
            }

            return $this->respond([
                'items' => $data,
                'total' => $total
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function content()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');
        try {
            $content = $this->fs->readFile($path);
            return $this->respond(['content' => $content]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function save()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $content = $json->content ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            // Versioning: Backup existing file before saving
            $username = session('username');
            $fullPath = $this->fs->resolvePath($path);
            $versionService = new \App\Services\VersionService($username);
            $versionService->createVersion($fullPath, $path);

            if (!$this->fs->writeFile($path, (string)($content ?? ''))) {
                throw new Exception('Unable to save file.');
            }
            LogService::log('Save File', $path);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function versionList()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');

        try {
            $username = session('username');
            $versionService = new \App\Services\VersionService($username);
            return $this->respond(['versions' => $versionService->listVersions($path)]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function versionRestore()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $versionId = $json->version_id ?? null;

        if (!$path || !$versionId) return $this->fail('Path and Version ID required');

        try {
            $username = session('username');
            $versionService = new \App\Services\VersionService($username);
            $versionService->restoreVersion($path, $versionId, $this->fs);
            LogService::log('Restore Version', $path, 'Version: ' . $versionId);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function rm()
    {
        if (!can('delete')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            // Use TrashService instead of direct delete
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            
            // Resolve full path to verify existence and for the move operation
            $fullPath = $this->fs->resolvePath($path);
            
            $trashService->moveToTrash($fullPath, $path);
            
            LogService::log('Move to Trash', $path);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function trashList()
    {
        if (!can('read')) return $this->failForbidden();
        try {
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            return $this->respond(['items' => $trashService->listItems()]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function trashRestore()
    {
        if (!can('delete')) return $this->failForbidden(); // Restore implies write/delete permission
        $json = $this->request->getJSON();
        $id = $json->id ?? null;

        if (!$id) return $this->fail('ID required');

        try {
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            $trashService->restore($id, $this->fs);
            LogService::log('Restore from Trash', $id);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function trashDelete()
    {
        if (!can('delete')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $id = $json->id ?? null;

        if (!$id) return $this->fail('ID required');

        try {
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            $trashService->deletePermanently($id);
            LogService::log('Permanent Delete', $id);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function trashEmpty()
    {
        if (!can('delete')) return $this->failForbidden();
        try {
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            $trashService->emptyTrash();
            LogService::log('Empty Trash', 'All items');
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function mkdir()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            if (!$this->fs->createDirectory($path)) {
                throw new Exception('Unable to create directory.');
            }
            LogService::log('Create Directory', $path);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function mv()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $from = $json->from ?? null;
        $to = $json->to ?? null;

        if (!$from || !$to) return $this->fail('From and To required');

        try {
            if (!$this->fs->move($from, $to)) {
                throw new Exception('Unable to move item.');
            }
            LogService::log('Move', $from, 'To: ' . $to);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function cp()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $from = $json->from ?? null;
        $to = $json->to ?? null;

        if (!$from || !$to) return $this->fail('From and To required');

        try {
            if (!$this->fs->copy($from, $to)) {
                throw new Exception('Unable to copy item.');
            }
            LogService::log('Copy', $from, 'To: ' . $to);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function isExtensionAllowed(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $allowed = session('allowed_extensions');
        $blocked = session('blocked_extensions');

        if ($allowed) {
            $allowedList = array_map('trim', explode(',', strtolower($allowed)));
            if (!in_array($ext, $allowedList)) {
                return false;
            }
        }

        if ($blocked) {
            $blockedList = array_map('trim', explode(',', strtolower($blocked)));
            if (in_array($ext, $blockedList)) {
                return false;
            }
        }

        // Hardened Default: Block dangerous extensions if not explicitly allowed
        if (empty($allowed)) {
            $dangerous = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'pl', 'py', 'rb', 'cgi', 'exe', 'sh', 'bat', 'cmd', 'htaccess', 'htpasswd'];
            if (in_array($ext, $dangerous)) {
                return false;
            }
        }

        return true;
    }

    public function upload()
    {
        if (!can('upload')) return $this->failForbidden();
        $path = $this->request->getPost('path') ?? '/';
        if ($path === '') {
            $path = '/';
        }
        $file = $this->request->getFile('file');
        $relativePath = $this->request->getPost('relativePath') ?? '';
        $conflict = $this->normalizeUploadConflict($this->request->getPost('conflict') ?? 'replace');

        if (!$file || !$file->isValid()) {
            return $this->fail($file ? $file->getErrorString() : 'No file uploaded');
        }

        try {
            $name = $this->sanitizeUploadFilename((string)$file->getClientName());
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        if (!$this->isExtensionAllowed($name)) {
            return $this->fail("Uploading files with this extension is not allowed.");
        }

        try {
            $settingsService = new \App\Services\SettingsService();
            $settings = $settingsService->getSettings();

            $fileSize = (int)$file->getSize();
            if ($this->exceedsMaxUploadSize($fileSize, $settings)) {
                $maxMb = (int)($settings['upload_max_file_mb'] ?? 0);
                return $this->fail("File exceeds the maximum allowed upload size of {$maxMb} MB.");
            }

            if ($this->wouldExceedUserQuota($fileSize, $settings)) {
                return $this->fail('Upload would exceed the configured per-user storage quota.');
            }

            $target = $this->resolveUploadTarget($path, $name, (string)$relativePath, $conflict);
            if ($target['skip']) {
                return $this->respond([
                    'status' => 'skipped',
                    'filename' => $target['filename'],
                    'path' => $target['relativePath'],
                ]);
            }

            if (!$file->move($target['dir'], $target['filename'], true)) {
                throw new Exception('Unable to store uploaded file.');
            }
            LogService::log('Upload', $path, 'File: ' . $target['relativePath']);
            return $this->respond([
                'status' => 'success',
                'filename' => $target['filename'],
                'path' => $target['relativePath'],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function uploadChunk()
    {
        if (!can('upload')) return $this->failForbidden();
        
        $file = $this->request->getFile('file');
        $filenameRaw = $this->request->getPost('filename');
        $chunkIndex = (int)$this->request->getPost('chunkIndex');
        $totalChunks = (int)$this->request->getPost('totalChunks');
        $declaredSizeRaw = $this->request->getPost('fileSize');
        $declaredSize = is_numeric($declaredSizeRaw) ? (int)$declaredSizeRaw : null;
        $targetPath = $this->request->getPost('path') ?? '/';
        if ($targetPath === '') {
            $targetPath = '/';
        }
        $relativePath = $this->request->getPost('relativePath') ?? '';
        $conflict = $this->normalizeUploadConflict($this->request->getPost('conflict') ?? 'replace');

        if (!$file || !$file->isValid()) return $this->fail('Invalid chunk');

        try {
            $filename = $this->sanitizeUploadFilename((string)$filenameRaw);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        if ($totalChunks < 1 || $chunkIndex < 0 || $chunkIndex >= $totalChunks) {
            return $this->fail('Invalid chunk metadata.');
        }

        if (!$this->isExtensionAllowed($filename)) {
            return $this->fail("Uploading files with this extension is not allowed.");
        }

        try {
            $settings = (new \App\Services\SettingsService())->getSettings();
            $chunkSize = (int)$file->getSize();
            if ($this->exceedsMaxUploadSize($chunkSize, $settings)) {
                $maxMb = (int)($settings['upload_max_file_mb'] ?? 0);
                return $this->fail("Chunk exceeds the maximum allowed upload size of {$maxMb} MB.");
            }

            $sessions = new UploadSessionService();
            $owner = (string)session('username');
            $legacyKey = $targetPath . '|' . $relativePath . '|' . $filename;
            $session = $sessions->legacy(
                $owner,
                $legacyKey,
                $targetPath,
                (string)$relativePath,
                $filename,
                $totalChunks,
                $declaredSize,
                max(UploadSessionService::MIN_CHUNK_SIZE, 1024 * 1024),
                $conflict
            );
            $manifest = $sessions->get($session['id']);
            $sessions->assertOwner($manifest, $owner);
            $stagingPath = $sessions->stagingPath($session['id']);
            if (!$file->move(dirname($stagingPath), basename($stagingPath), false)) {
                return $this->fail('Unable to store upload chunk.', 500);
            }
            try {
                $sessions->storeChunkFromPath($session['id'], $chunkIndex, $stagingPath, $chunkSize);
            } finally {
                if (is_file($stagingPath)) {
                    @unlink($stagingPath);
                }
            }

            if ($chunkIndex !== $totalChunks - 1) {
                return $this->respond(['status' => 'chunk_saved', 'index' => $chunkIndex]);
            }

            $manifest = $sessions->get($session['id']);
            $missing = $sessions->missingChunks($manifest);
            if ($missing !== []) {
                return $this->respond([
                    'status' => 'chunk_saved',
                    'index' => $chunkIndex,
                    'missing' => $missing,
                ], 202);
            }

            $settingsService = new \App\Services\SettingsService();
            $settings = $settingsService->getSettings();
            $assembledSize = array_sum(array_map('intval', $manifest['chunks'] ?? []));
            if ($this->exceedsMaxUploadSize($assembledSize, $settings)) {
                $sessions->abort($session['id']);
                $maxMb = (int)($settings['upload_max_file_mb'] ?? 0);
                return $this->fail("File exceeds the maximum allowed upload size of {$maxMb} MB.");
            }
            if ($this->wouldExceedUserQuota($assembledSize, $settings)) {
                $sessions->abort($session['id']);
                return $this->fail('Upload would exceed the configured per-user storage quota.');
            }

            $target = $this->resolveUploadTarget($targetPath, $filename, (string)$relativePath, $conflict);
            if ($target['skip']) {
                $sessions->abort($session['id']);
                return $this->respond([
                    'status' => 'skipped',
                    'filename' => $target['filename'],
                    'path' => $target['relativePath'],
                ]);
            }

            $finalPath = $target['dir'] . DIRECTORY_SEPARATOR . $target['filename'];
            $sessions->assemble($session['id'], $finalPath);
            LogService::log('Upload (Chunked)', $targetPath, 'File: ' . $target['relativePath']);
            return $this->respond([
                'status' => 'assembled',
                'filename' => $target['filename'],
                'path' => $target['relativePath'],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function uploadSessionCreate()
    {
        if (!can('upload')) return $this->failForbidden();

        $json = $this->request->getJSON(true) ?? [];
        $filename = (string)($json['filename'] ?? '');
        $targetPath = (string)($json['path'] ?? '/');
        $relativePath = (string)($json['relativePath'] ?? '');
        $totalSize = filter_var($json['totalSize'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $chunkSize = (int)($json['chunkSize'] ?? 1024 * 1024);
        $totalChunks = filter_var($json['totalChunks'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $conflict = $this->normalizeUploadConflict((string)($json['conflict'] ?? 'replace'));

        try {
            $filename = $this->sanitizeUploadFilename($filename);
            if (!$this->isExtensionAllowed($filename)) {
                return $this->fail('Uploading files with this extension is not allowed.');
            }
            $this->fs->resolvePath($targetPath);
            $this->sanitizeUploadRelativeSegments($relativePath);
            $settings = (new \App\Services\SettingsService())->getSettings();
            if ($totalSize === false || $this->exceedsMaxUploadSize((int)$totalSize, $settings)) {
                return $this->fail('Upload exceeds the configured size limit.', 413);
            }

            $service = new UploadSessionService();
            $session = $service->create(
                (string)session('username'),
                $targetPath,
                $relativePath,
                $filename,
                (int)$totalSize,
                $chunkSize,
                $totalChunks === false ? null : (int)$totalChunks,
                $conflict
            );

            return $this->respondCreated(['status' => 'created'] + $session);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function uploadSessionChunk(string $id, int $index)
    {
        if (!can('upload')) return $this->failForbidden();

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->fail('Invalid upload chunk.');
        }

        try {
            $service = new UploadSessionService();
            $manifest = $service->get($id);
            $service->assertOwner($manifest, (string)session('username'));
            $size = (int)$file->getSize();
            $stagingPath = $service->stagingPath($id);
            if (!$file->move(dirname($stagingPath), basename($stagingPath), false)) {
                return $this->fail('Unable to store upload chunk.', 500);
            }
            try {
                $service->storeChunkFromPath($id, $index, $stagingPath, $size);
            } finally {
                if (is_file($stagingPath)) {
                    @unlink($stagingPath);
                }
            }

            return $this->respond(['status' => 'chunk_saved', 'index' => $index]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function uploadSessionComplete(string $id)
    {
        if (!can('upload')) return $this->failForbidden();

        try {
            $service = new UploadSessionService();
            $manifest = $service->get($id);
            $service->assertOwner($manifest, (string)session('username'));
            $settings = (new \App\Services\SettingsService())->getSettings();
            $size = array_sum(array_map('intval', $manifest['chunks'] ?? []));
            if ($this->exceedsMaxUploadSize($size, $settings) || $this->wouldExceedUserQuota($size, $settings)) {
                $service->abort($id);
                return $this->fail('Upload exceeds the configured storage limit.', 413);
            }

            $target = $this->resolveUploadTarget(
                (string)$manifest['target_path'],
                (string)$manifest['filename'],
                (string)$manifest['relative_path'],
                (string)$manifest['conflict']
            );
            if ($target['skip']) {
                $service->abort($id);
                return $this->respond([
                    'status' => 'skipped',
                    'filename' => $target['filename'],
                    'path' => $target['relativePath'],
                ]);
            }

            $service->assemble($id, $target['dir'] . DIRECTORY_SEPARATOR . $target['filename']);
            LogService::log('Upload (Resumable)', (string)$manifest['target_path'], 'File: ' . $target['relativePath']);
            return $this->respond([
                'status' => 'assembled',
                'filename' => $target['filename'],
                'path' => $target['relativePath'],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function uploadSessionAbort(string $id)
    {
        if (!can('upload')) return $this->failForbidden();

        try {
            $service = new UploadSessionService();
            $manifest = $service->get($id);
            $service->assertOwner($manifest, (string)session('username'));
            $service->abort($id);
            return $this->respond(['status' => 'aborted']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function download()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        $inline = $this->request->getGet('inline');
        
        if (!$path) return $this->fail('Path required');

        try {
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
                $body = file_get_contents($tempZip);
                @unlink($tempZip);
                if ($body === false) {
                    throw new Exception('Unable to read download archive.');
                }
                return $this->response
                    ->setHeader('Content-Type', 'application/zip')
                    ->setHeader('Content-Disposition', DownloadHeaders::contentDisposition('attachment', $zipName))
                    ->setBody($body);
            }

            if (!is_array($metadata)) {
                return $this->failNotFound('File not found');
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
                $body = $fullPath !== null ? file_get_contents($fullPath) : $this->fs->readFile($path);
                if ($body === false) {
                    throw new Exception('Unable to read file.');
                }

                return $this->response
                    ->setHeader('Content-Type', $mime)
                    ->setHeader('Content-Disposition', DownloadHeaders::contentDisposition('inline', $filename))
                    ->setBody($body);
            }

            if ($fullPath !== null) {
                return $this->response
                    ->download($fullPath, null)
                    ->setFileName(DownloadHeaders::filename($filename));
            }

            return $this->response
                ->setHeader('Content-Type', (string)($metadata['mime'] ?? 'application/octet-stream'))
                ->setHeader('Content-Disposition', DownloadHeaders::contentDisposition('attachment', $filename))
                ->setBody($this->fs->readFile($path));
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
            if ($sourceSize === false || $sourceSize > 25 * 1024 * 1024) {
                return $this->fail('Image is too large to generate a thumbnail.', 413);
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

    public function chown()
    {
        if (!can('admin_users')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $paths = $json->paths ?? [$json->path ?? null];
        $user = $json->user ?? null;
        $group = $json->group ?? null;
        $recursive = (bool)($json->recursive ?? false);

        if (empty(array_filter($paths))) return $this->fail('Paths required');

        try {
            foreach ($paths as $path) {
                if ($path && !$this->fs->chown($path, $user, $group, $recursive)) {
                    throw new Exception('Unable to change ownership.');
                }
            }
            LogService::log('Chown' . ($recursive ? ' (Recursive)' : ''), implode(', ', $paths), 'User: ' . $user . ', Group: ' . $group);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    // --- Share API ---

    public function sharePolicy()
    {
        if (!can('read')) return $this->failForbidden();

        try {
            $settingsService = new \App\Services\SettingsService();
            $settings = $settingsService->getSettings();
            return $this->respond($this->buildSharePolicy($settings));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function shareCreate()
    {
        if (!can('read')) return $this->failForbidden();
        
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $password = $json->password ?? null;
        $expires = $json->expires ?? null; // Timestamp or ISO string? Let's assume timestamp from frontend
        $mode = strtolower(trim((string)($json->mode ?? 'read')));
        
        if (!$path) return $this->fail('Path required');
        
        // Verify existence within user jail
        try {
            $absolutePath = $this->fs->resolvePath($path); // Throws if invalid/traversal
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        try {
            $settingsService = new \App\Services\SettingsService();
            $settings = $settingsService->getSettings();
            $policy = $this->buildSharePolicy($settings);

            $allowedModes = $policy['available_modes'] ?? ['read'];
            if (!in_array($mode, $allowedModes, true)) {
                return $this->fail('Share mode is not allowed by policy.');
            }

            if ($mode === 'upload' && !empty($absolutePath) && !is_dir($absolutePath)) {
                return $this->fail('Upload-mode shares must target a folder.');
            }

            if (!empty($settings['share_require_password']) && empty($password)) {
                return $this->fail('A password is required by policy for shared links.');
            }

            $expiresAt = $this->normalizeShareExpiry($expires, $settings);
            $service = new \App\Services\ShareService();
            $share = $service->createShare($path, session('username'), $password, $expiresAt, $mode);
            LogService::log('Create Share', $path);
            return $this->respond(['status' => 'success', 'share' => $share]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function shareDelete()
    {
        if (!can('read')) return $this->failForbidden();

        $json = $this->request->getJSON();
        $hash = $json->hash ?? null;
        if (!$hash) return $this->fail('Hash required');

        try {
            $service = new \App\Services\ShareService();
            $share = $service->getShareRaw($hash);
            
            // Allow admin to delete any share, user only their own
            if ($share && ($share['created_by'] === session('username') || can('admin_users'))) {
                // If it is a transfer, delete the physical directory as well.
                if (isset($share['source']) && $share['source'] === 'transfer') {
                    $dir = config('Storage')->uploads . '/shares/' . $share['path'];
                    $this->rrmdir($dir);
                }
                $service->deleteShare($hash);
                LogService::log('Delete Share', $hash);
                return $this->respond(['status' => 'success']);
            }
            return $this->failForbidden();
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function shareList()
    {
        if (!can('read')) return $this->failForbidden();

        try {
            $service = new \App\Services\ShareService();
            $shares = $service->listUserShares(session('username'));
            return $this->respond(['items' => $shares]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Normalizes and enforces share expiry policies.
     *
     * @param mixed $expires A timestamp or strtotime-compatible string.
     * @param array $settings Settings array from SettingsService.
     */
    private function normalizeShareExpiry($expires, array $settings): ?int
    {
        $now = time();
        $maxDays = (int)($settings['share_max_expiry_days'] ?? 30);
        if ($maxDays < 1) {
            $maxDays = 1;
        }
        if ($maxDays > 365) {
            $maxDays = 365;
        }

        $defaultDays = (int)($settings['share_default_expiry_days'] ?? 7);
        if ($defaultDays < 1) {
            $defaultDays = 1;
        }
        if ($defaultDays > $maxDays) {
            $defaultDays = $maxDays;
        }

        $requireExpiry = !empty($settings['share_require_expiry']);

        $expiresAt = null;
        if ($expires !== null && $expires !== '') {
            if (is_numeric($expires)) {
                $expiresAt = (int)$expires;
            } else {
                $ts = strtotime((string)$expires);
                if ($ts === false) {
                    throw new Exception('Share expiry is not a valid date.');
                }
                $expiresAt = $ts;
            }
        }

        if ($expiresAt === null && $requireExpiry) {
            $expiresAt = $now + ($defaultDays * 86400);
        }

        if ($expiresAt !== null && $expiresAt <= $now) {
            throw new Exception('Share expiry must be in the future.');
        }

        if ($expiresAt !== null) {
            $maxTimestamp = $now + ($maxDays * 86400);
            if ($expiresAt > $maxTimestamp) {
                throw new Exception("Share expiry exceeds the maximum allowed of {$maxDays} days.");
            }
        }

        return $expiresAt;
    }

    private function buildSharePolicy(array $settings): array
    {
        $maxDays = (int)($settings['share_max_expiry_days'] ?? 30);
        if ($maxDays < 1) {
            $maxDays = 1;
        }
        if ($maxDays > 365) {
            $maxDays = 365;
        }

        $defaultDays = (int)($settings['share_default_expiry_days'] ?? 7);
        if ($defaultDays < 1) {
            $defaultDays = 1;
        }
        if ($defaultDays > $maxDays) {
            $defaultDays = $maxDays;
        }

        $allowUploadMode = !empty($settings['allow_public_uploads']);
        $availableModes = ['read'];
        if ($allowUploadMode) {
            $availableModes[] = 'upload';
        }

        $allowedExtensions = $this->normalizeAllowedExtensions($settings['share_upload_allowed_extensions'] ?? []);
        $allowedExtensionsLabel = '';
        if (!empty($allowedExtensions)) {
            $allowedExtensionsLabel = implode(', ', array_map(static fn (string $ext): string => '.' . $ext, $allowedExtensions));
        }

        return [
            'require_password' => !empty($settings['share_require_password']),
            'require_expiry' => !empty($settings['share_require_expiry']),
            'default_expiry_days' => $defaultDays,
            'max_expiry_days' => $maxDays,
            'allow_upload_mode' => $allowUploadMode,
            'available_modes' => $availableModes,
            'upload_policy' => [
                'max_file_mb' => (int)($settings['upload_max_file_mb'] ?? 0),
                'allowed_extensions' => $allowedExtensions,
                'allowed_extensions_label' => $allowedExtensionsLabel,
                'quota_mb' => (int)($settings['share_upload_quota_mb'] ?? 0),
                'max_files' => (int)($settings['share_upload_max_files'] ?? 0),
            ],
        ];
    }

    /**
     * Normalize allowed extensions from settings input.
     *
     * @param mixed $raw
     * @return array<int, string>
     */
    private function normalizeAllowedExtensions($raw): array
    {
        $extensions = [];

        if (is_string($raw)) {
            $extensions = preg_split('/[\s,;]+/', $raw) ?: [];
        } elseif (is_array($raw)) {
            $extensions = $raw;
        }

        $normalized = [];
        foreach ($extensions as $ext) {
            $ext = strtolower(trim((string)$ext));
            $ext = ltrim($ext, '.');
            if ($ext === '') {
                continue;
            }
            if (!preg_match('/^[a-z0-9]+$/', $ext)) {
                continue;
            }
            $normalized[] = $ext;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($path) && !is_link($path)) {
                $this->rrmdir($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($dir);
    }

    private function exceedsMaxUploadSize(int $bytes, array $settings): bool
    {
        $maxMb = (int)($settings['upload_max_file_mb'] ?? 0);
        if ($maxMb <= 0) {
            return false;
        }
        $maxBytes = $maxMb * 1024 * 1024;
        return $bytes > $maxBytes;
    }

    private function normalizeUploadConflict(string $conflict): string
    {
        $conflict = strtolower(trim($conflict));
        if (!in_array($conflict, ['replace', 'skip', 'keep_both'], true)) {
            return 'replace';
        }

        return $conflict;
    }

    /**
     * @return array{dir: string, filename: string, relativePath: string, skip: bool}
     */
    private function resolveUploadTarget(string $basePath, string $filename, string $relativePath, string $conflict): array
    {
        $targetDir = $this->fs->resolvePath($basePath);
        if (!is_dir($targetDir)) {
            throw new Exception('Target directory does not exist.');
        }

        $segments = $this->sanitizeUploadRelativeSegments($relativePath);
        if ($segments !== []) {
            $filename = array_pop($segments) ?: $filename;
        }

        if ($segments !== []) {
            foreach ($segments as $segment) {
                $targetDir .= DIRECTORY_SEPARATOR . $segment;
                if (is_link($targetDir)) {
                    throw new Exception('Upload path contains a symbolic link.');
                }
                if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                    throw new Exception('Unable to create upload folder.');
                }
            }
        }

        $filename = $this->sanitizeUploadFilename($filename);
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (is_link($targetPath) || file_exists($targetPath)) {
            if ($conflict === 'skip') {
                return [
                    'dir' => $targetDir,
                    'filename' => $filename,
                    'relativePath' => $this->joinUploadRelativePath($segments, $filename),
                    'skip' => true,
                ];
            }

            if ($conflict === 'keep_both') {
                if (!is_file($targetPath)) {
                    throw new Exception('An upload directory already uses this name.');
                }
                $filename = $this->nextUploadFilename($targetDir, $filename);
            } elseif (!is_file($targetPath)) {
                throw new Exception('An upload directory already uses this name.');
            }
        }

        return [
            'dir' => $targetDir,
            'filename' => $filename,
            'relativePath' => $this->joinUploadRelativePath($segments, $filename),
            'skip' => false,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function sanitizeUploadRelativeSegments(string $relativePath): array
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        if ($relativePath === '') {
            return [];
        }

        $segments = [];
        if (str_starts_with($relativePath, '/') || preg_match('/\A[A-Za-z]:[\\\/]/', $relativePath)) {
            throw new Exception('Upload path must be relative.');
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                throw new Exception('Upload path traversal is not allowed.');
            }
            $segments[] = $this->sanitizeUploadFilename($segment);
        }

        return $segments;
    }

    /**
     * @param array<int, string> $segments
     */
    private function joinUploadRelativePath(array $segments, string $filename): string
    {
        return implode('/', array_merge($segments, [$filename]));
    }

    private function nextUploadFilename(string $targetDir, string $filename): string
    {
        $info = pathinfo($filename);
        $base = $info['filename'] ?? $filename;
        $extension = isset($info['extension']) && $info['extension'] !== '' ? '.' . $info['extension'] : '';

        for ($i = 1; $i < 1000; $i++) {
            $candidate = $base . ' (' . $i . ')' . $extension;
            if (!file_exists($targetDir . DIRECTORY_SEPARATOR . $candidate) && !is_link($targetDir . DIRECTORY_SEPARATOR . $candidate)) {
                return $candidate;
            }
        }

        throw new Exception('Unable to create a non-conflicting filename.');
    }

    private function wouldExceedUserQuota(int $incomingBytes, array $settings): bool
    {
        $connection = session('connection');
        if (($connection['mode'] ?? 'local') !== 'local') {
            return false;
        }

        $quotaMb = (int)($settings['quota_per_user_mb'] ?? 0);
        if ($quotaMb <= 0) {
            return false;
        }

        $quotaBytes = $quotaMb * 1024 * 1024;
        $homePath = $this->getUserHomePath();
        $limit = $quotaBytes - $incomingBytes;
        if ($limit <= 0) {
            return true;
        }

        $currentUsage = $this->calculateDirectorySize($homePath, $limit + 1);
        return ($currentUsage + $incomingBytes) > $quotaBytes;
    }

    private function getUserHomePath(): string
    {
        $homeDir = (string)(session('home_dir') ?? '/');
        return (new LocalAdapter(config('Storage')->fileManagerRoot))->resolvePath($homeDir);
    }

    private function calculateDirectorySize(string $path, ?int $stopAtBytes = null): int
    {
        if (is_link($path)) {
            throw new Exception('Quota path contains a symbolic link.');
        }
        if (!is_dir($path)) {
            return 0;
        }

        $items = scandir($path);
        if ($items === false) {
            throw new Exception('Unable to scan quota path.');
        }

        $total = 0;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $child = $path . DIRECTORY_SEPARATOR . $item;
            if (is_link($child)) {
                throw new Exception('Quota path contains a symbolic link.');
            }
            if (is_dir($child)) {
                $remaining = $stopAtBytes === null ? null : max(1, $stopAtBytes - $total);
                $total += $this->calculateDirectorySize($child, $remaining);
            } elseif (is_file($child)) {
                $size = filesize($child);
                if ($size === false) {
                    throw new Exception('Unable to read quota file size.');
                }
                $total += (int)$size;
            }
            if ($stopAtBytes !== null && $total >= $stopAtBytes) {
                return $total;
            }
        }

        return $total;
    }

    private function sanitizeUploadFilename(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new Exception('Filename is required.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
            throw new Exception('Filename contains invalid characters.');
        }
        if (preg_match('/[\/\\\\]/', $name)) {
            throw new Exception('Invalid filename.');
        }
        if (strlen($name) > 255) {
            throw new Exception('Filename is too long.');
        }

        $name = basename($name);
        if ($name === '' || $name === '.' || $name === '..') {
            throw new Exception('Invalid filename.');
        }

        return $name;
    }
}
