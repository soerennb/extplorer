<?php

namespace App\Controllers;

use App\Services\ShareService;
use App\Services\EmailService;
use App\Services\SettingsService;
use App\Services\LogService;
use App\Services\VFS\VfsFactory;
use App\Services\ResourcePolicy;
use App\Services\FileNamePolicy;
use App\Services\StagingResourceService;

class TransferController extends BaseController
{
    use ApiResponseTrait;

    private ShareService $shareService;
    private EmailService $emailService;
    private SettingsService $settingsService;
    private StagingResourceService $stagingResources;

    public function __construct()
    {
        $this->shareService = new ShareService();
        $this->emailService = new EmailService();
        $this->settingsService = new SettingsService();
        $this->stagingResources = new StagingResourceService();
    }

    public function status()
    {
        if (!can('read')) {
            return $this->failForbidden();
        }

        $sessionId = $this->normalizeSessionId((string)$this->request->getGet('sessionId'));
        $fileName = $this->request->getGet('fileName');

        if (!$sessionId || strlen($sessionId) < 16 || !$fileName) {
            return $this->fail('Missing parameters');
        }

        try {
            $fileName = $this->sanitizeTransferFilename((string)$fileName);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        $fileName = basename($fileName);
        $tempDir = $this->getTransferTempDir($sessionId);
        $tempPath = $tempDir . '/' . $fileName . '.part';

        try {
            $this->assertNoSymlinkComponents($tempDir);
        } catch (\Throwable $e) {
            return $this->fail('Invalid transfer storage.', 500);
        }
        if (is_link($tempDir) || is_link($tempPath) || is_link($tempDir . '/' . $fileName)) {
            return $this->fail('Invalid transfer storage.', 500);
        }

        // Check if full file already exists
        if (file_exists($tempDir . '/' . $fileName)) {
             return $this->respond([
                'status' => 'complete',
                'uploaded' => filesize($tempDir . '/' . $fileName)
            ]);
        }

        if (file_exists($tempPath)) {
            return $this->respond([
                'status' => 'partial',
                'uploaded' => filesize($tempPath)
            ]);
        }

        return $this->respond([
            'status' => 'new',
            'uploaded' => 0
        ]);
    }

    public function capability()
    {
        if (!can('read')) {
            return $this->failForbidden();
        }

        $status = $this->emailService->deliveryStatus();
        return $this->respond([
            'available' => $status['ready'],
            'reason' => $status['reason'],
            'settings_url' => null,
        ]);
    }

    /**
     * Stage internal files for transfer.
     */
    public function stage()
    {
        if (!can('read')) {
            return $this->failForbidden();
        }

        if (($gate = $this->emailDeliveryGate()) !== null) {
            return $gate;
        }

        $json = $this->request->getJSON();
        $sessionId = $this->normalizeSessionId((string)($json->sessionId ?? ''));
        $paths = $json->paths ?? [];

        if (!$sessionId || empty($paths) || !is_array($paths)) {
            return $this->fail('Missing parameters');
        }

        $tempDir = $this->getTransferTempDir($sessionId);
        try {
            $this->assertNoSymlinkComponents($tempDir);
        } catch (\Throwable $e) {
            return $this->fail('Invalid transfer storage.', 500);
        }
        if (!is_dir($tempDir) && !mkdir($tempDir, 0700, true) && !is_dir($tempDir)) {
            return $this->fail('Server Error: Cannot create transfer storage.', 500);
        }

        try {
            $sessionLock = $this->openTransferSessionLock($sessionId);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }

        try {
            $this->assertNoSymlinkComponents($tempDir);
            if (is_link($tempDir)) {
                return $this->fail('Invalid transfer storage.', 500);
            }
            $vfs = VfsFactory::createFileSystem(
                (string)(session('username') ?? ''),
                (array)(session('connection') ?? ['mode' => 'local'])
            );
            $stagedCount = 0;
            $totalBytes = 0;

            foreach ($paths as $path) {
            $meta = $vfs->getMetadata((string)$path);
            if (!$meta || (($meta['type'] ?? '') === 'dir')) {
                continue; // Skip directories or non-existent files for now
            }
            
            // Validate size limits
            $size = (int)($meta['size'] ?? 0);
            $maxFileBytes = $this->getMaxUploadBytes();
            if ($maxFileBytes > 0 && $size > $maxFileBytes) {
                return $this->fail("File '$path' exceeds the configured size limit.");
            }
            
            // Read content from VFS and write to temp dir
            try {
                $fileName = $this->sanitizeTransferFilename(basename(str_replace('\\', '/', (string)$path)));
            } catch (\Throwable $e) {
                return $this->fail($e->getMessage());
            }
            $destPath = $tempDir . '/' . $fileName;

            if (is_link($destPath)) {
                return $this->fail('Invalid transfer storage.', 500);
            }

            $before = $this->getTransferStagingStats($tempDir);
            $existingSize = is_file($destPath) ? (int)filesize($destPath) : 0;
            $existingFiles = is_file($destPath) ? 1 : 0;
            try {
                $this->reserveTransferStaging(
                    $sessionId,
                    $tempDir,
                    $before['bytes'] - $existingSize + $size,
                    $before['files'] - $existingFiles + 1
                );
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'staging resource limit')) {
                    return $this->fail('Transfer staging resource limit exceeded.', 413);
                }
                return $this->fail($e->getMessage(), 500);
            }

            $stream = null;
            $output = null;
            $copied = 0;
            $activated = false;
            $temporaryPath = $destPath . '.part-' . bin2hex(random_bytes(8));
            if (is_link($temporaryPath)) {
                return $this->fail('Invalid transfer storage.', 500);
            }
            try {
                $stream = $vfs->openReadStream((string)$path);
                $output = fopen($temporaryPath, 'wb');
                if ($output === false) {
                    throw new \RuntimeException('Unable to create transfer staging file.');
                }
                $copied = (new ResourcePolicy())->copyStream($stream, $output, $maxFileBytes > 0 ? $maxFileBytes : null);
                if ($size > 0 && $copied !== $size) {
                    throw new \RuntimeException('Transfer source changed while it was being staged.');
                }
                if (!fclose($output)) {
                    $output = null;
                    throw new \RuntimeException('Unable to finalize transfer staging file.');
                }
                $output = null;
                // Reserve the measured stream size before activation. A lying
                // source metadata value must not create an over-limit file that
                // remains behind when the post-activation accounting fails.
                $this->reserveTransferStaging(
                    $sessionId,
                    $tempDir,
                    $before['bytes'] - $existingSize + $copied,
                    $before['files'] - $existingFiles + 1
                );
                if (!rename($temporaryPath, $destPath)) {
                    throw new \RuntimeException('Unable to activate transfer staging file.');
                }
                $activated = true;
                // Source metadata is advisory. Recalculate the actual staged
                // tree after activation so a changing/lying source cannot
                // bypass aggregate staging limits.
                $this->syncTransferReservation($sessionId, $tempDir);
            } catch (\Throwable $e) {
                @unlink($temporaryPath);
                if ($activated) {
                    @unlink($destPath);
                }
                try {
                    $this->syncTransferReservation($sessionId, $tempDir);
                } catch (\Throwable) {
                    // The original failure is more actionable to the caller.
                }
                if (str_contains($e->getMessage(), 'staging resource limit')) {
                    return $this->fail('Transfer staging resource limit exceeded.', 413);
                }
                continue;
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                if (is_resource($output)) {
                    fclose($output);
                }
            }
            
            $stagedCount++;
            $totalBytes += $copied;
            }

            return $this->respond([
                'status' => 'success',
                'count' => $stagedCount,
                'bytes' => $totalBytes
            ]);
        } finally {
            $this->closeTransferSessionLock($sessionLock);
        }
    }

    /**
     * Upload a file chunk.
     */
    public function upload()
    {
        if (!can('upload')) {
            LogService::log('Transfer Upload Forbidden', '', 'Blocked: missing upload permission');
            return $this->failForbidden();
        }

        if (($gate = $this->emailDeliveryGate()) !== null) {
            return $gate;
        }

        $file = $this->request->getFile('file');
        $sessionId = $this->normalizeSessionId((string)$this->request->getPost('sessionId'));
        $fileName = $this->request->getPost('fileName');
        $chunkIndex = (int)$this->request->getPost('chunkIndex');
        $totalChunks = (int)$this->request->getPost('totalChunks');
        $fileOffset = (int)($this->request->getPost('fileOffset') ?? 0);
        $fileSize = (int)($this->request->getPost('fileSize') ?? 0);

        if (!$file || !$sessionId || strlen($sessionId) < 16 || !$fileName || $chunkIndex < 0
            || $totalChunks < 1 || $totalChunks > 100000 || $chunkIndex >= $totalChunks) {
            return $this->fail('Missing parameters');
        }

        try {
            $fileName = $this->sanitizeTransferFilename((string)$fileName);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        if (!$file->isValid() || $fileSize < 1 || $fileOffset < 0) {
            return $this->fail('Invalid transfer chunk metadata.');
        }

        $chunkBytes = (int)$file->getSize();
        if ($fileOffset > $fileSize || $chunkBytes < 1 || $chunkBytes > 8 * 1024 * 1024
            || $chunkBytes > ($fileSize - $fileOffset)) {
            return $this->fail('Invalid transfer chunk size.');
        }

        $maxFileBytes = $this->getMaxUploadBytes();
        if ($maxFileBytes > 0 && $fileSize > $maxFileBytes) {
            return $this->fail('File exceeds the configured upload size limit.');
        }

        $tempDir = $this->getTransferTempDir($sessionId);
        try {
            $this->assertNoSymlinkComponents($tempDir);
        } catch (\Throwable $e) {
            return $this->fail('Invalid transfer storage.', 500);
        }
        
        if (!is_dir($tempDir) && !mkdir($tempDir, 0700, true) && !is_dir($tempDir)) {
            return $this->fail('Server Error: Cannot create transfer storage.');
        }

        try {
            $sessionLock = $this->openTransferSessionLock($sessionId);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }

        try {
            $this->assertNoSymlinkComponents($tempDir);
            if (is_link($tempDir)) {
                return $this->fail('Invalid transfer storage.', 500);
            }
            $tempPath = $tempDir . '/' . $fileName . '.part';

            if (is_link($tempPath)) {
                return $this->fail('Invalid transfer storage.');
            }

            $lock = @fopen($tempPath . '.lock', 'c');
            if ($lock === false || !flock($lock, LOCK_EX)) {
                if (is_resource($lock)) fclose($lock);
                return $this->fail('Server Error: Cannot lock transfer upload.');
            }

            try {
            $existingSize = file_exists($tempPath) ? (int)filesize($tempPath) : 0;
            if ($fileOffset !== $existingSize) {
                return $this->fail('Upload offset mismatch; please resume the transfer.');
            }

            $before = $this->getTransferStagingStats($tempDir);
            $existingFile = is_file($tempPath) ? 1 : 0;
            try {
                $this->reserveTransferStaging(
                    $sessionId,
                    $tempDir,
                    $before['bytes'] - $existingSize + $fileSize,
                    $before['files'] - $existingFile + 1
                );
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'staging resource limit')) {
                    return $this->fail('Transfer staging resource limit exceeded.', 413);
                }
                throw $e;
            }

            $input = fopen($file->getTempName(), 'rb');
            $output = fopen($tempPath, 'c+b');
            if ($input === false || $output === false) {
                if (is_resource($input)) fclose($input);
                if (is_resource($output)) fclose($output);
                return $this->fail('Server Error: Cannot write upload.');
            }

            if (fseek($output, $fileOffset) !== 0) {
                fclose($input);
                fclose($output);
                return $this->fail('Server Error: Cannot seek upload.');
            }

            $copied = stream_copy_to_stream($input, $output);
            fclose($input);
            if ($copied !== $chunkBytes || !fflush($output)) {
                fclose($output);
                return $this->fail('Server Error: Cannot write complete upload chunk.');
            }
            fclose($output);

            $currentSize = (int)filesize($tempPath);
            if ($chunkIndex === $totalChunks - 1) {
                if ($currentSize !== $fileSize) {
                    return $this->fail('Upload incomplete; please resume the transfer.');
                }
                $finalPath = $tempDir . '/' . $fileName;
                if (is_link($finalPath)) {
                    return $this->fail('Invalid transfer storage.', 500);
                }
                if (!rename($tempPath, $finalPath)) {
                    return $this->fail('Server Error: Cannot finalize upload.');
                }
            }

            return $this->respond([
                'status' => $chunkIndex === $totalChunks - 1 ? 'complete' : 'partial',
                'uploaded' => $currentSize
            ]);
            } finally {
                try {
                    $this->syncTransferReservation($sessionId, $tempDir);
                } catch (\Throwable $e) {
                    log_message('error', 'Unable to refresh transfer staging reservation: ' . $e->getMessage());
                }
                flock($lock, LOCK_UN);
                fclose($lock);
                @unlink($tempPath . '.lock');
            }
        } finally {
            $this->closeTransferSessionLock($sessionLock);
        }
    }

    /**
     * Finalize and Send Transfer
     */
    public function send()
    {
        if (!can('read')) {
            LogService::log('Transfer Send Forbidden', '', 'Blocked: missing read permission');
            return $this->failForbidden();
        }

        if (($gate = $this->emailDeliveryGate()) !== null) {
            return $gate;
        }

        $throttler = \Config\Services::throttler();
        $sendThrottleKey = 'transfer-send-' . hash('sha256', (string)session('username')) . '-' . hash('sha256', $this->request->getIPAddress());
        if ($throttler->check($sendThrottleKey, 15, MINUTE) === false) {
            LogService::log('Transfer Send Throttled', '', 'Rate limit exceeded for transfer send');
            return $this->fail('Too many requests. Please slow down.', 429);
        }

        $json = $this->request->getJSON();
        $sessionIdRaw = $json->sessionId ?? '';
        // Guard against event objects or other non-scalar values being passed from the UI.
        $sessionId = $this->normalizeSessionId(is_scalar($sessionIdRaw) ? (string)$sessionIdRaw : '');
        $recipients = $this->normalizeRecipients($json->recipients ?? []); // Array of emails
        try {
            $subject = $this->sanitizeEmailSubject((string)($json->subject ?? ''));
            $message = $this->sanitizeEmailMessage((string)($json->message ?? ''));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        $expiryDays = $this->clampExpiryDays((int)($json->expiresIn ?? $this->settingsService->get('default_transfer_expiry')));
        $notifyDefault = (bool)$this->settingsService->get('transfer_default_notify_download', false);
        $notifyDownload = (bool)($json->notifyDownload ?? $notifyDefault);
        
        if (!$sessionId || empty($recipients)) {
            return $this->fail('Missing parameters');
        }

        $tempDir = $this->getTransferTempDir($sessionId);
        if (!is_dir($tempDir)) {
            return $this->fail('Upload session expired or invalid');
        }

        try {
            $sessionLock = $this->openTransferSessionLock($sessionId);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }

        try {
            try {
                $this->assertNoSymlinkComponents($tempDir);
                if (is_link($tempDir)) {
                    return $this->fail('Invalid transfer storage.', 500);
                }
                $this->syncTransferReservation($sessionId, $tempDir);
            } catch (\Throwable $e) {
                return $this->fail('Transfer staging resource limit exceeded.', 413);
            }

        // Generate Hash and move files
        $hash = bin2hex(random_bytes(8)); // Use Service to ensure unique?
        // Actually, let's just use createShare to get the hash, then move files.
        // We pass a dummy path first, then update it? Or use the hash in the path.
        // Strategy: Create Share first.
        
        // Define storage path
        $relPath = $hash; // For transfers, path is just the Hash folder name in uploads/shares
        $absPath = $this->shareService->resolveTransferDirectory($relPath);
        
        if (!mkdir($absPath, 0700, true) && !is_dir($absPath)) {
            return $this->fail('Server Error: Cannot create storage', 500);
        }
        // Re-resolve after creation. A filesystem race must not turn the
        // generated transfer directory into a symlink target.
        try {
            $absPath = $this->shareService->resolveTransferDirectory($relPath, true);
        } catch (\Throwable $e) {
            return $this->fail('Invalid transfer storage.', 500);
        }

        // Move files
        $files = scandir($tempDir);
        $fileList = [];
        $totalSize = 0;
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_ends_with($f, '.part') || str_ends_with($f, '.lock') || str_starts_with($f, '.part-')) {
                continue;
            }
            $source = $tempDir . '/' . $f;
            if (is_link($source) || !is_file($source)) {
                $this->rrmdir($absPath);
                return $this->fail('Invalid transfer storage.', 500);
            }
            try {
                $safeName = $this->sanitizeTransferFilename($f);
            } catch (\Throwable $e) {
                $this->rrmdir($absPath);
                return $this->fail($e->getMessage());
            }
            $destination = $absPath . '/' . $safeName;
            if (file_exists($destination) || is_link($destination) || !rename($source, $destination)) {
                $this->rrmdir($absPath);
                return $this->fail('Unable to finalize transfer file.', 500);
            }
            $size = filesize($destination);
            if ($size === false) {
                $this->rrmdir($absPath);
                return $this->fail('Unable to read transfer file size.', 500);
            }
            $fileList[] = $safeName;
            $totalSize += (int)$size;
        }
        $this->cleanupTempDir($tempDir);
        $this->releaseTransferReservation($sessionId);

        if (empty($fileList)) {
            $this->rrmdir($absPath);
            return $this->fail('No files uploaded');
        }

        // Enforce upload size limits.
        $maxFileBytes = $this->getMaxUploadBytes();
        if ($maxFileBytes > 0) {
            foreach ($fileList as $name) {
                $size = (int)filesize($absPath . '/' . $name);
                if ($size > $maxFileBytes) {
                    $this->rrmdir($absPath);
                    return $this->fail('One or more files exceed the configured upload size limit.');
                }
            }
        }

        // Enforce per-user transfer quota if configured.
        $quotaBytes = $this->getUserQuotaBytes();
        if ($quotaBytes > 0) {
            $currentUsage = $this->getUserTransferUsageBytes(session('username'));
            if (($currentUsage + $totalSize) > $quotaBytes) {
                $this->rrmdir($absPath);
                return $this->fail('Transfer would exceed your configured storage quota.');
            }
        }

        // Create Share Record
        $meta = [
            'is_transfer' => true,
            'source' => 'transfer', // explicit flag for ShareController
            'recipients' => $recipients,
            'sender_email' => $this->getSenderEmail(),
            'subject' => $subject,
            'message' => $message,
            'notify_download' => $notifyDownload,
            'file_count' => count($fileList),
            'total_size' => $totalSize
        ];

        $expiresAt = time() + ($expiryDays * 86400);

        // We use $relPath as the 'path' in share. 
        // ShareController will need to know that source='transfer' means look in WRITEPATH/uploads/shares/
        try {
            $share = $this->shareService->createShare($relPath, session('username'), null, $expiresAt, 'read', $meta);
        } catch (\Throwable $e) {
            $this->rrmdir($absPath);
            return $this->fail($e->getMessage(), 500);
        }

        // Send Emails
        $link = site_url('s/' . $share['hash']);
        $emailFailures = [];
        foreach ($recipients as $email) {
            $ok = $this->emailService->sendTransferNotification([
                'sender_email' => $this->getSenderEmail() ?? session('username'),
                'recipient_email' => $email,
                'subject' => $subject,
                'message' => $message
            ], $link);
            if (!$ok) {
                $emailFailures[] = $email;
            }
        }

        if ($emailFailures !== []) {
            $this->shareService->deleteShare($share['hash']);
            $this->rrmdir($absPath);
            $this->emailService->invalidateDeliveryVerification();
            LogService::log(
                'Transfer Email Failed',
                "Hash: {$share['hash']}",
                'Transfer was rolled back because at least one notification failed.'
            );
            return $this->fail('Unable to send the transfer notification. Email delivery must be tested again.', 502);
        }

        LogService::log('Transfer Sent', "Hash: {$share['hash']}, Files: " . count($fileList));

        return $this->respond([
            'status' => 'success',
            'link' => $link,
            'email_failures' => $emailFailures,
        ]);
        } finally {
            try {
                $this->syncTransferReservation($sessionId, $tempDir);
            } catch (\Throwable $e) {
                log_message('error', 'Unable to finalize transfer staging reservation: ' . $e->getMessage());
            }
            $this->closeTransferSessionLock($sessionLock);
        }
    }

    private function emailDeliveryGate()
    {
        if ($this->emailService->isDeliveryReady()) {
            return null;
        }

        $status = $this->emailService->deliveryStatus();
        LogService::log('Transfer Blocked', '', 'Email delivery is not ready: ' . $status['reason']);
        return $this->fail('Email delivery must be configured and tested before sending files.', 503);
    }

    /**
     * List User Transfers
     */
    public function history()
    {
        if (!can('read')) {
            return $this->failForbidden();
        }

        $user = session('username');
        $shares = $this->shareService->listUserShares($user);
        
        // Filter only transfers
        $transfers = array_filter($shares, fn($s) => isset($s['is_transfer']) && $s['is_transfer']);
        
        // Sort by date desc
        usort($transfers, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        $now = time();
        $items = array_map(function (array $t) use ($now): array {
            $view = $this->shareService->transferView($t);
            $expiresAt = (int)($view['expires_at'] ?? 0);
            $downloads = (int)($view['downloads'] ?? 0);
            $expired = $expiresAt > 0 && $now > $expiresAt;
            $status = $expired ? 'expired' : ($downloads > 0 ? 'downloaded' : 'active');
            $view['status'] = $status;
            $view['is_expired'] = $expired;
            $view['expires_in'] = $expiresAt > 0 ? max(0, $expiresAt - $now) : null;
            return $view;
        }, array_values($transfers));

        return $this->respond($items);
    }

    /**
     * Delete a transfer
     */
    public function delete($hash)
    {
        if (!can('delete') && !can('admin_users')) {
            LogService::log('Transfer Delete Forbidden', (string)$hash, 'Blocked: missing delete/admin permission');
            return $this->failForbidden();
        }

        $hash = is_scalar($hash) ? (string)$hash : '';
        if (!$this->shareService->isValidHash($hash)) return $this->failNotFound();
        $share = $this->shareService->getShareRaw($hash);
        if (!$share) return $this->failNotFound();

        if ($share['created_by'] !== session('username') && !can('admin_users')) {
            return $this->failForbidden();
        }

        // Delete physical files
        if (isset($share['source']) && $share['source'] === 'transfer') {
            $dir = $this->shareService->resolveTransferDirectory((string)($share['path'] ?? ''), true);
            // Recursive delete
            $this->rrmdir($dir);
        }

        $this->shareService->deleteShare($hash);
        return $this->respond(['status' => 'success']);
    }

    private function rrmdir($dir) {
        if (is_dir($dir) && !is_link($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

    private function normalizeSessionId(string $sessionId): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]/', '', $sessionId) ?? '';
        return strlen($normalized) >= 16 && strlen($normalized) <= 64 ? $normalized : '';
    }

    private function sanitizeTransferFilename(string $fileName): string
    {
        $fileName = basename(str_replace('\\', '/', $fileName));
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            throw new \RuntimeException('Invalid transfer filename.');
        }
        if (strlen($fileName) > 255 || preg_match('/[\x00-\x1F\x7F]/', $fileName)) {
            throw new \RuntimeException('Invalid transfer filename.');
        }

        return (new FileNamePolicy())->assertSafe($fileName);
    }

    private function getTransferTempDir(string $sessionId): string
    {
        $user = (string)(session('username') ?? '');
        $userKey = hash('sha256', $user !== '' ? $user : 'anonymous');
        return config('Storage')->uploads . '/temp/' . $userKey . '/' . $sessionId;
    }

    private function normalizeRecipients($recipients): array
    {
        if (!is_array($recipients)) {
            return [];
        }

        $normalized = [];
        foreach ($recipients as $recipient) {
            if (!is_scalar($recipient)) {
                continue;
            }
            $email = strtolower(trim((string)$recipient));
            if ($email === '') {
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $normalized[$email] = true;
            if (count($normalized) >= 25) {
                break;
            }
        }

        return array_keys($normalized);
    }

    private function sanitizeEmailSubject(string $subject): string
    {
        $subject = trim((string)preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', $subject));
        if (mb_strlen($subject) > 200) {
            throw new \RuntimeException('Email subject is too long.');
        }

        return $subject;
    }

    private function sanitizeEmailMessage(string $message): string
    {
        $message = trim($message);
        if (mb_strlen($message) > 20_000) {
            throw new \RuntimeException('Email message is too long.');
        }

        return $message;
    }

    private function clampExpiryDays(int $days): int
    {
        $maxDays = (int)$this->settingsService->get('transfer_max_expiry_days', 30);
        if ($maxDays < 1) {
            $maxDays = 1;
        }
        if ($maxDays > 365) {
            $maxDays = 365;
        }

        if ($days <= 0) {
            $defaultDays = (int)$this->settingsService->get('default_transfer_expiry');
            if ($defaultDays < 1) {
                $defaultDays = 1;
            }
            return min($maxDays, $defaultDays);
        }
        return max(1, min($maxDays, $days));
    }

    private function getSenderEmail(): ?string
    {
        $email = session('email');
        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        return null;
    }

    private function cleanupTempDir(string $dir): void
    {
        if (is_link($dir)) {
            throw new \RuntimeException('Transfer staging uses a symbolic link.');
        }
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_link($path) || is_file($path)) {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    private function getMaxUploadBytes(): int
    {
        $maxMb = (int)$this->settingsService->get('upload_max_file_mb', 0);
        if ($maxMb <= 0) {
            return 0;
        }
        // Cap to a sane upper bound to avoid overflow surprises.
        if ($maxMb > 10240) {
            $maxMb = 10240;
        }
        return $maxMb * 1024 * 1024;
    }

    private function getUserQuotaBytes(): int
    {
        $quotaMb = (int)$this->settingsService->get('quota_per_user_mb', 0);
        if ($quotaMb <= 0) {
            return 0;
        }
        if ($quotaMb > 102400) {
            $quotaMb = 102400;
        }
        return $quotaMb * 1024 * 1024;
    }

    private function getUserTransferUsageBytes(string $user): int
    {
        if ($user === '') {
            return 0;
        }

        $now = time();
        $usage = 0;
        foreach ($this->shareService->getAllShares() as $share) {
            if (($share['created_by'] ?? '') !== $user) {
                continue;
            }
            if (empty($share['is_transfer'])) {
                continue;
            }
            $expiresAt = (int)($share['expires_at'] ?? 0);
            if ($expiresAt > 0 && $now > $expiresAt) {
                continue;
            }
            $usage += (int)($share['total_size'] ?? 0);
        }

        return $usage;
    }

    /** @return resource */
    private function openTransferSessionLock(string $sessionId)
    {
        $tempDir = $this->getTransferTempDir($sessionId);
        $parent = dirname($tempDir);
        $this->assertNoSymlinkComponents($parent);
        if (is_link($tempDir)) {
            throw new \RuntimeException('Transfer staging uses a symbolic link.');
        }
        if (!is_dir($parent) && !mkdir($parent, 0700, true) && !is_dir($parent)) {
            throw new \RuntimeException('Unable to create transfer lock storage.');
        }
        $this->assertNoSymlinkComponents($parent);
        if (is_link($parent)) {
            throw new \RuntimeException('Transfer staging uses a symbolic link.');
        }
        $lock = @fopen($parent . DIRECTORY_SEPARATOR . '.session-' . $sessionId . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new \RuntimeException('Unable to lock transfer staging.');
        }

        return $lock;
    }

    /** @param resource $lock */
    private function closeTransferSessionLock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /** @return array{bytes: int, files: int} */
    private function getTransferStagingStats(string $directory): array
    {
        if (is_link($directory)) {
            throw new \RuntimeException('Invalid transfer storage.');
        }
        if (!is_dir($directory)) {
            return ['bytes' => 0, 'files' => 0];
        }

        $bytes = 0;
        $files = 0;
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || str_ends_with($entry, '.lock')) {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            if (is_link($path) || !is_file($path)) {
                throw new \RuntimeException('Invalid transfer storage.');
            }
            $size = filesize($path);
            if ($size === false) {
                throw new \RuntimeException('Unable to read transfer staging size.');
            }
            $bytes += (int)$size;
            $files++;
        }

        return ['bytes' => $bytes, 'files' => $files];
    }

    private function transferReservationId(string $sessionId): string
    {
        return 'transfer:' . hash('sha256', (string)session('username') . '|' . $sessionId);
    }

    private function reserveTransferStaging(string $sessionId, string $directory, int $bytes, int $files): void
    {
        $this->stagingResources->reserve(
            (string)session('username'),
            $this->transferReservationId($sessionId),
            $bytes,
            $files,
            [
                'kind' => 'transfer',
                'session_id' => $sessionId,
                'directory' => $directory,
            ]
        );
    }

    private function syncTransferReservation(string $sessionId, string $directory): void
    {
        if (!is_dir($directory)) {
            $this->stagingResources->release($this->transferReservationId($sessionId));
            return;
        }
        $stats = $this->getTransferStagingStats($directory);
        $this->reserveTransferStaging($sessionId, $directory, $stats['bytes'], $stats['files']);
    }

    private function releaseTransferReservation(string $sessionId): void
    {
        $this->stagingResources->release(
            $this->transferReservationId($sessionId),
            (string)session('username')
        );
    }

    private function assertNoSymlinkComponents(string $path): void
    {
        $current = rtrim($path, '/\\');
        while ($current !== dirname($current)) {
            if (is_link($current)) {
                throw new \RuntimeException('Transfer staging uses a symbolic link.');
            }
            $current = dirname($current);
        }
    }
}
