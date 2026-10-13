<?php

namespace App\Services;

use RuntimeException;

/** Validates and publishes a transfer while retaining retryable source payloads. */
final class TransferService
{
    private ShareService $shares;
    private EmailService $email;
    private TransferStagingService $staging;
    private TransferPolicy $policy;

    public function __construct(?ShareService $shares = null, ?EmailService $email = null, ?TransferStagingService $staging = null, ?TransferPolicy $policy = null)
    {
        $this->shares = $shares ?? new ShareService();
        $this->email = $email ?? new EmailService();
        $this->staging = $staging ?? new TransferStagingService();
        $this->policy = $policy ?? new TransferPolicy();
    }

    public function send(string $owner, array $request): array
    {
        if (!$this->email->isDeliveryReady()) throw new RuntimeException('Email delivery must be configured and tested before sending files.', 503);
        return $this->staging->withSession($owner, $request['session_id'], function (string $directory) use ($owner, $request): array {
            $files = $this->staging->completedFiles($directory);
            if ($files['files'] === []) throw new RuntimeException('No files uploaded');
            foreach ($files['files'] as $name => $size) {
                $this->policy->filename($name);
                if ($this->policy->maxFileBytes() > 0 && $size > $this->policy->maxFileBytes()) throw new RuntimeException('One or more files exceed the configured upload size limit.');
            }
            $relative = bin2hex(random_bytes(8));
            $destination = $this->shares->resolveTransferDirectory($relative);
            if (!mkdir($destination, 0700)) throw new RuntimeException('Unable to create transfer storage.', 500);
            $destination = $this->shares->resolveTransferDirectory($relative, true);
            $share = null;
            $emailFailures = [];
            try {
                // Copy rather than consume the session: no failure below may
                // destroy the only copy of an already uploaded file.
                foreach ($files['files'] as $name => $size) {
                    $input = fopen($directory . '/' . $name, 'rb');
                    $output = fopen($destination . '/' . $name, 'xb');
                    try {
                        $copied = (new ResourcePolicy())->copyStream($input, $output, max(1, $size));
                        if ($copied !== $size) throw new RuntimeException('Transfer source changed while finalizing.');
                    } finally {
                        if (is_resource($input)) fclose($input);
                        if (is_resource($output)) fclose($output);
                    }
                }
                $share = OperationLock::run(config('Storage')->uploads . '/.transfer-quota-' . hash('sha256', $owner) . '.lock', function () use ($owner, $request, $relative, $files): array {
                    if ($this->policy->quotaBytes() > 0 && $this->usage($owner) + $files['bytes'] > $this->policy->quotaBytes()) throw new RuntimeException('Transfer would exceed your configured storage quota.');
                    return $this->shares->createShare($relative, $owner, null, $request['expires_at'], 'read', [
                        'is_transfer' => true, 'source' => 'transfer', 'recipients' => $request['recipients'],
                        'sender_email' => $request['sender_email'], 'subject' => $request['subject'], 'message' => $request['message'],
                        'notify_download' => $request['notify_download'], 'file_count' => count($files['files']), 'total_size' => $files['bytes'],
                    ]);
                });
                $link = site_url('s/' . $share['hash']);
                foreach ($request['recipients'] as $recipient) {
                    try {
                        $delivered = $this->email->sendTransferNotification([
                            'sender_email' => $request['sender_email'] ?? $owner, 'recipient_email' => $recipient,
                            'subject' => $request['subject'], 'message' => $request['message'],
                        ], $link);
                    } catch (\Throwable $exception) {
                        log_message('error', 'Transfer notification failed: {message}', ['message' => $exception->getMessage()]);
                        $delivered = false;
                    }
                    if (!$delivered) $emailFailures[] = $recipient;
                }
                if ($emailFailures !== []) $this->email->invalidateDeliveryVerification();
                if (count($emailFailures) === count($request['recipients'])) throw new RuntimeException('Unable to send the transfer notification. Email delivery must be tested again.', 502);
            } catch (\Throwable $exception) {
                if ($share !== null) $this->shares->deleteShare($share['hash']);
                PrivateFileTree::remove($destination);
                throw $exception;
            }
            // Delivery is committed. Cleanup failure must not invalidate a link
            // recipients already received, or report a misleading send failure.
            try { $this->staging->finish($owner, $request['session_id']); }
            catch (\Throwable $exception) { log_message('error', 'Unable to remove committed transfer staging: {message}', ['message' => $exception->getMessage()]); }
            LogService::log('Transfer Sent', 'Hash: ' . $share['hash'] . ', Files: ' . count($files['files']), '', $owner);
            return ['status' => 'success', 'link' => $link, 'email_failures' => $emailFailures];
        });
    }

    public function delete(array $share): void
    {
        if (($share['source'] ?? '') === 'transfer') PrivateFileTree::remove($this->shares->resolveTransferDirectory((string)$share['path'], true));
        $this->shares->deleteShare($share['hash']);
    }

    private function usage(string $owner): int
    {
        $bytes = 0;
        foreach ($this->shares->getAllShares() as $share) {
            if (($share['created_by'] ?? '') === $owner && !empty($share['is_transfer'])
                && (empty($share['expires_at']) || (int)$share['expires_at'] > time())) $bytes += (int)($share['total_size'] ?? 0);
        }
        return $bytes;
    }
}
