<?php

namespace App\Controllers;

use App\Services\FileNamePolicy;
use App\Services\FileUploadPolicy;
use App\Services\FileUploadService;
use App\Services\LogService;
use App\Services\UploadChunkInput;
use App\Services\UploadSessionService;
use App\Services\UploadTargetResolver;
use App\Services\VFS\UploadTarget;
use RuntimeException;

/** HTTP transport adapters share the same payload acceptance and activation service. */
trait ApiTransferOperationsTrait
{
    public function upload()
    {
        if (!can('upload')) return $this->failForbidden();
        try {
            $file = $this->request->getFile('file');
            if (!$file || !$file->isValid()) throw new RuntimeException($file ? $file->getErrorString() : 'No file uploaded');
            $result = (new FileUploadService())->upload($file->getTempName(), $this->fs, (string)session('username'),
                (array)(session('connection') ?? ['mode' => 'local']), $this->uploadPostString('path', '/'),
                $file->getClientName(), $this->uploadPostString('relativePath'),
                (new FileUploadPolicy())->conflict($this->uploadPostString('conflict', 'replace')));
            return $this->uploadResult($result, 'Upload');
        } catch (\Throwable $exception) { return $this->uploadFailure($exception); }
    }

    public function uploadChunk()
    {
        if (!can('upload')) return $this->failForbidden();
        try {
            $file = $this->request->getFile('file');
            if (!$file || !$file->isValid()) throw new RuntimeException('Invalid chunk');
            $policy = new FileUploadPolicy();
            $filename = $policy->filename($this->uploadPostString('filename'));
            $base = $this->uploadPostString('path', '/');
            $relative = $this->uploadPostString('relativePath');
            $index = (int)$this->request->getPost('chunkIndex');
            $count = (int)$this->request->getPost('totalChunks');
            $declared = $this->request->getPost('fileSize');
            $size = is_numeric($declared) ? (int)$declared : null;
            $conflict = $policy->conflict($this->uploadPostString('conflict', 'replace'));
            $sessions = new UploadSessionService();
            $key = $base . '|' . $relative . '|' . $filename;
            $connection = (array)(session('connection') ?? ['mode' => 'local']);
            if (($connection['mode'] ?? 'local') !== 'local') $key .= '|' . \App\Services\RemoteUploadCredentials::fingerprint($connection);
            $session = $sessions->legacy((string)session('username'), $key, $base, $relative, $filename, $count, $size, 1024 * 1024, $conflict);
            $this->bindUploadTarget($sessions, $session['id']);
            (new UploadChunkInput())->store($this->request, $sessions, $session['id'], $index);
            $manifest = $sessions->get($session['id']);
            $missing = $sessions->missingChunks($manifest);
            if ($index !== $count - 1 || $missing !== []) {
                $result = ['status' => 'chunk_saved', 'index' => $index];
                if ($index === $count - 1 && $missing !== []) $result['missing'] = $missing;
                return $this->respond($result, isset($result['missing']) ? 202 : 200);
            }
            return $this->completeUpload($sessions, $session['id']);
        } catch (\Throwable $exception) { return $this->uploadFailure($exception); }
    }

    public function uploadSessionCreate()
    {
        if (!can('upload')) return $this->failForbidden();
        try {
            $input = $this->request->getJSON(true);
            if (!is_array($input)) throw new RuntimeException('A JSON object is required.');
            $policy = new FileUploadPolicy();
            $filename = $policy->filename($this->uploadJsonString($input, 'filename'));
            $size = filter_var($input['totalSize'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($size === false) throw new RuntimeException('Invalid upload size.');
            $policy->assertSize($size);
            $count = filter_var($input['totalChunks'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $sessions = new UploadSessionService();
            $session = $sessions->create((string)session('username'), $this->uploadJsonString($input, 'path', '/'),
                $this->uploadJsonString($input, 'relativePath'), $filename, $size, (int)($input['chunkSize'] ?? 1024 * 1024),
                $count === false ? null : $count, $policy->conflict($this->uploadJsonString($input, 'conflict', 'replace')));
            try { $this->bindUploadTarget($sessions, $session['id']); }
            catch (\Throwable $exception) { $sessions->abort($session['id']); throw $exception; }
            return $this->respondCreated(['status' => 'created'] + $session);
        } catch (\Throwable $exception) { return $this->uploadFailure($exception); }
    }

    public function uploadSessionChunk(string $id, int $index)
    {
        if (!can('upload')) return $this->failForbidden();
        try {
            $sessions = new UploadSessionService();
            $this->bindUploadTarget($sessions, $id);
            (new UploadChunkInput())->store($this->request, $sessions, $id, $index);
            return $this->respond(['status' => 'chunk_saved', 'index' => $index]);
        } catch (\Throwable $exception) { return $this->uploadFailure($exception); }
    }

    public function uploadSessionComplete(string $id)
    {
        if (!can('upload')) return $this->failForbidden();
        try { return $this->completeUpload(new UploadSessionService(), $id); }
        catch (\Throwable $exception) { return $this->uploadFailure($exception); }
    }

    public function uploadSessionAbort(string $id)
    {
        if (!can('upload')) return $this->failForbidden();
        try {
            $sessions = new UploadSessionService();
            $manifest = $sessions->get($id);
            $sessions->assertOwner($manifest, (string)session('username'));
            $sessions->abort($id);
            return $this->respond(['status' => 'aborted']);
        } catch (\Throwable $exception) { return $this->uploadFailure($exception); }
    }

    private function bindUploadTarget(UploadSessionService $sessions, string $id): void
    {
        $manifest = $sessions->get($id);
        $owner = (string)session('username');
        $connection = (array)(session('connection') ?? ['mode' => 'local']);
        $sessions->assertOwner($manifest, $owner);
        $resolver = new UploadTargetResolver();
        if (isset($manifest['target'])) {
            $resolver->assertContext(UploadTarget::fromArray($manifest['target']), $owner, $connection);
            return;
        }
        $prepared = (new FileUploadService())->prepare($this->fs, (string)$manifest['target_path'],
            (string)$manifest['filename'], (string)$manifest['relative_path']);
        if (!(new FileNamePolicy())->isAllowed($prepared['filename'], session('allowed_extensions'), session('blocked_extensions'))) throw new RuntimeException('Uploading files with this extension is not allowed.');
        $sessions->bindTarget($id, $resolver->capture($owner, $connection, $prepared['path']));
    }

    private function completeUpload(UploadSessionService $sessions, string $id)
    {
        $this->bindUploadTarget($sessions, $id);
        $result = $sessions->finalize($id, function (array $manifest, string $payload): array {
            $result = (new FileUploadService())->accept($payload, UploadTarget::fromArray($manifest['target']),
                (string)$manifest['conflict'], (new FileUploadPolicy())->target((string)$manifest['target_path'],
                    (string)$manifest['filename'], (string)$manifest['relative_path'])['relativePath']);
            if ($result['status'] === 'success') $result['status'] = 'assembled';
            return $result;
        });
        return $this->uploadResult($result, 'Upload (Chunked)');
    }

    private function uploadResult(array $result, string $action)
    {
        LogService::log($action, $result['path'] ?? '', 'File: ' . ($result['filename'] ?? ''));
        return $this->respond($result, $result['status'] === 'pending_scan' ? 202 : 200);
    }

    private function uploadFailure(\Throwable $exception)
    {
        $status = $exception->getCode();
        return $this->fail($exception->getMessage(), $status >= 400 && $status <= 599 ? $status : 400);
    }

    private function uploadPostString(string $key, string $default = ''): string
    {
        $value = $this->request->getPost($key) ?? $default;
        if (!is_string($value)) throw new RuntimeException('Invalid upload parameter.');
        return $value;
    }

    private function uploadJsonString(array $input, string $key, string $default = ''): string
    {
        $value = $input[$key] ?? $default;
        if (!is_string($value)) throw new RuntimeException('Invalid upload parameter.');
        return $value;
    }
}
