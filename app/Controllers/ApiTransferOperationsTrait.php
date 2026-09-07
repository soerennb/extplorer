<?php

namespace App\Controllers;

use App\Services\VFS\LocalAdapter;
use App\Services\LogService;
use App\Services\UploadSessionService;
use Exception;

trait ApiTransferOperationsTrait
{
    private function isExtensionAllowed(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = session('allowed_extensions');
        $blocked = session('blocked_extensions');

        if ($allowed && !in_array($ext, array_map('trim', explode(',', strtolower($allowed))), true)) {
            return false;
        }
        if ($blocked && in_array($ext, array_map('trim', explode(',', strtolower($blocked))), true)) {
            return false;
        }
        if (empty($allowed) && in_array($ext, ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'pl', 'py', 'rb', 'cgi', 'exe', 'sh', 'bat', 'cmd', 'htaccess', 'htpasswd'], true)) {
            return false;
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
