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

    /**
     * Upload a file chunk.
     */
    public function upload()
    {
        $file = $this->request->getFile('file');
        $sessionId = $this->request->getPost('sessionId');
        $fileName = $this->request->getPost('fileName');
        $chunkIndex = (int)$this->request->getPost('chunkIndex');
        $totalChunks = (int)$this->request->getPost('totalChunks');

        if (!$file || !$sessionId || !$fileName) {
            return $this->fail('Missing parameters');
        }

        // Sanitize Filename
        $fileName = basename($fileName);
        $tempDir = WRITEPATH . 'uploads/temp/' . preg_replace('/[^a-zA-Z0-9]/', '', $sessionId);
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempPath = $tempDir . '/' . $fileName . '.part';

        // Append chunk
        $input = fopen($file->getTempName(), 'rb');
        $output = fopen($tempPath, 'ab'); // Append binary
        stream_copy_to_stream($input, $output);
        fclose($input);
        fclose($output);

        // Calculate size so far
        $currentSize = filesize($tempPath);

        // If last chunk, rename
        if ($chunkIndex === $totalChunks - 1) {
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
        $sessionId = $json->sessionId ?? '';
        $recipients = $json->recipients ?? []; // Array of emails
        $subject = $json->subject ?? '';
        $message = $json->message ?? '';
        $expiryDays = (int)($json->expiresIn ?? $this->settingsService->get('default_transfer_expiry'));
        $notifyDownload = $json->notifyDownload ?? false;
        
        if (!$sessionId || empty($recipients)) {
            return $this->fail('Missing parameters');
        }

        $tempDir = WRITEPATH . 'uploads/temp/' . preg_replace('/[^a-zA-Z0-9]/', '', $sessionId);
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
            rename($tempDir . '/' . $f, $absPath . '/' . $f);
            $fileList[] = $f;
            $totalSize += filesize($absPath . '/' . $f);
        }
        rmdir($tempDir);

        if (empty($fileList)) {
            return $this->fail('No files uploaded');
        }

        // Create Share Record
        $meta = [
            'is_transfer' => true,
            'source' => 'transfer', // explicit flag for ShareController
            'recipients' => $recipients,
            'sender_email' => session('email') ?? 'user@local', // Need to get email from profile if available
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
                'sender_email' => session('username'), // Or email if we had it
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

        return $this->respond(array_values($transfers));
    }

    /**
     * Delete a transfer
     */
    public function delete($hash)
    {
        $share = $this->shareService->getShare($hash);
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
}
