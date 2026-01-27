<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Services\ShareService;
use App\Services\EmailService;
use App\Services\SettingsService;
use App\Services\LogService;

class TransferController extends BaseController
{
    use ResponseTrait;

    private ShareService $shareService;
    private EmailService $emailService;
    private SettingsService $settingsService;

    public function __construct()
    {
        $this->shareService = new ShareService();
        $this->emailService = new EmailService();
        $this->settingsService = new SettingsService();
    }

    public function status()
    {
        $sessionId = $this->normalizeSessionId((string)$this->request->getGet('sessionId'));
        $fileName = $this->request->getGet('fileName');

        if (!$sessionId || !$fileName) {
            return $this->fail('Missing parameters');
        }

        $fileName = basename($fileName);
        $tempDir = WRITEPATH . 'uploads/temp/' . $sessionId;
        $tempPath = $tempDir . '/' . $fileName . '.part';

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

    /**
     * Upload a file chunk.
     */
    public function upload()
    {
        $file = $this->request->getFile('file');
        $sessionId = $this->normalizeSessionId((string)$this->request->getPost('sessionId'));
        $fileName = $this->request->getPost('fileName');
        $chunkIndex = (int)$this->request->getPost('chunkIndex');
        $totalChunks = (int)$this->request->getPost('totalChunks');
        $fileOffset = (int)($this->request->getPost('fileOffset') ?? 0);
        $fileSize = (int)($this->request->getPost('fileSize') ?? 0);

        if (!$file || !$sessionId || !$fileName) {
            return $this->fail('Missing parameters');
        }

        // Sanitize Filename
        $fileName = basename($fileName);
        $tempDir = WRITEPATH . 'uploads/temp/' . $sessionId;
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempPath = $tempDir . '/' . $fileName . '.part';

        $existingSize = file_exists($tempPath) ? (int)filesize($tempPath) : 0;
        if ($fileOffset < 0 || $fileOffset > $existingSize) {
            return $this->fail('Upload offset mismatch; please resume the transfer.');
        }

        // Write at the provided byte offset to make resume safe.
        $input = fopen($file->getTempName(), 'rb');
        $data = stream_get_contents($input);
        fclose($input);

        $output = fopen($tempPath, 'c+b');
        if ($output === false) {
            return $this->fail('Server Error: Cannot write upload');
        }

        if (fseek($output, $fileOffset) !== 0) {
            fclose($output);
            return $this->fail('Server Error: Cannot seek upload');
        }

        fwrite($output, $data);
        fflush($output);
        fclose($output);

        // Calculate size so far
        $currentSize = filesize($tempPath);

        // If last chunk, rename
        if ($chunkIndex === $totalChunks - 1) {
            if ($fileSize > 0 && $currentSize !== $fileSize) {
                return $this->fail('Upload incomplete; please resume the transfer.');
            }
            rename($tempPath, $tempDir . '/' . $fileName);
        }

        return $this->respond([
            'status' => 'success',
            'uploaded' => $currentSize
        ]);
    }

    /**
     * Finalize and Send Transfer
     */
    public function send()
    {
        $json = $this->request->getJSON();
        $sessionId = $this->normalizeSessionId((string)($json->sessionId ?? ''));
        $recipients = $this->normalizeRecipients($json->recipients ?? []); // Array of emails
        $subject = trim((string)($json->subject ?? ''));
        $message = trim((string)($json->message ?? ''));
        $expiryDays = $this->clampExpiryDays((int)($json->expiresIn ?? $this->settingsService->get('default_transfer_expiry')));
        $notifyDefault = (bool)$this->settingsService->get('transfer_default_notify_download', false);
        $notifyDownload = (bool)($json->notifyDownload ?? $notifyDefault);
        
        if (!$sessionId || empty($recipients)) {
            return $this->fail('Missing parameters');
        }

        $tempDir = WRITEPATH . 'uploads/temp/' . $sessionId;
        if (!is_dir($tempDir)) {
            return $this->fail('Upload session expired or invalid');
        }

        // Generate Hash and move files
        $hash = bin2hex(random_bytes(8)); // Use Service to ensure unique?
        // Actually, let's just use createShare to get the hash, then move files.
        // We pass a dummy path first, then update it? Or use the hash in the path.
        // Strategy: Create Share first.
        
        // Define storage path
        $relPath = $hash; // For transfers, path is just the Hash folder name in uploads/shares
        $absPath = WRITEPATH . 'uploads/shares/' . $hash;
        
        if (!mkdir($absPath, 0777, true)) {
            return $this->fail('Server Error: Cannot create storage');
        }

        // Move files
        $files = scandir($tempDir);
        $fileList = [];
        $totalSize = 0;
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_ends_with($f, '.part')) {
                continue;
            }
            rename($tempDir . '/' . $f, $absPath . '/' . $f);
            $fileList[] = $f;
            $totalSize += filesize($absPath . '/' . $f);
        }
        $this->cleanupTempDir($tempDir);

        if (empty($fileList)) {
            return $this->fail('No files uploaded');
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
        $share = $this->shareService->createShare($relPath, session('username'), null, $expiresAt, 'read', $meta);

        // Send Emails
        $link = site_url('s/' . $share['hash']);
        foreach ($recipients as $email) {
            $this->emailService->sendTransferNotification([
                'sender_email' => $this->getSenderEmail() ?? session('username'),
                'recipient_email' => $email,
                'subject' => $subject,
                'message' => $message
            ], $link);
        }

        LogService::log('Transfer Sent', "Hash: {$share['hash']}, Files: " . count($fileList));

        return $this->respond([
            'status' => 'success',
            'link' => $link
        ]);
    }

    /**
     * List User Transfers
     */
    public function history()
    {
        $user = session('username');
        $shares = $this->shareService->listUserShares($user);
        
        // Filter only transfers
        $transfers = array_filter($shares, fn($s) => isset($s['is_transfer']) && $s['is_transfer']);
        
        // Sort by date desc
        usort($transfers, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        $now = time();
        $items = array_map(function (array $t) use ($now): array {
            $expiresAt = (int)($t['expires_at'] ?? 0);
            $downloads = (int)($t['downloads'] ?? 0);
            $expired = $expiresAt > 0 && $now > $expiresAt;
            $status = $expired ? 'expired' : ($downloads > 0 ? 'downloaded' : 'active');
            $t['status'] = $status;
            $t['is_expired'] = $expired;
            $t['expires_in'] = $expiresAt > 0 ? max(0, $expiresAt - $now) : null;
            return $t;
        }, array_values($transfers));

        return $this->respond($items);
    }

    /**
     * Delete a transfer
     */
    public function delete($hash)
    {
        $share = $this->shareService->getShareRaw($hash);
        if (!$share) return $this->failNotFound();

        if ($share['created_by'] !== session('username') && !can('admin_users')) {
            return $this->failForbidden();
        }

        // Delete physical files
        if (isset($share['source']) && $share['source'] === 'transfer') {
            $dir = WRITEPATH . 'uploads/shares/' . $share['path'];
            // Recursive delete
            $this->rrmdir($dir);
        }

        $this->shareService->deleteShare($hash);
        return $this->respond(['status' => 'success']);
    }

    private function rrmdir($dir) {
        if (is_dir($dir)) {
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
        return preg_replace('/[^a-zA-Z0-9]/', '', $sessionId) ?? '';
    }

    private function normalizeRecipients($recipients): array
    {
        if (!is_array($recipients)) {
            return [];
        }

        $normalized = [];
        foreach ($recipients as $recipient) {
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
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            @unlink($dir . DIRECTORY_SEPARATOR . $entry);
        }
        @rmdir($dir);
    }
}
