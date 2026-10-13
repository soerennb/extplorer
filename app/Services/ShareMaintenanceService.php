<?php

namespace App\Services;

/** Cleanup owns slow side effects; repository transactions only commit metadata. */
final class ShareMaintenanceService
{
    public function __construct(private ShareService $shares, private ?EmailService $email = null)
    {
        $this->email ??= new EmailService();
    }

    public function cleanup(): array
    {
        $stats = ['expired' => 0, 'warned' => 0];
        foreach (array_keys($this->shares->getAllShares()) as $hash) {
            if (!$this->shares->isValidHash((string)$hash)) continue;
            $result = OperationLock::run(config('Storage')->runtime . '/.share-cleanup-' . $hash . '.lock', function () use ($hash): array {
                $share = $this->shares->getShareRaw($hash);
                if ($share === null) return [];
                $now = time();
                if (!empty($share['expires_at']) && $now >= (int)$share['expires_at']) {
                    if (($share['source'] ?? '') === 'transfer') {
                        try { PrivateFileTree::remove($this->shares->resolveTransferDirectory((string)$share['path'], true)); }
                        catch (\Throwable $exception) {
                            log_message('error', 'Unable to clean up expired transfer share: {message}', ['message' => $exception->getMessage()]);
                            return []; // Retain metadata so cleanup can be retried.
                        }
                    }
                    $this->shares->deleteShare($hash);
                    return ['expired' => 1];
                }
                $lifetime = (int)($share['expires_at'] ?? 0) - (int)($share['created_at'] ?? 0);
                if ($lifetime > 0 && empty($share['warning_sent']) && empty($share['downloads'])
                    && $now - (int)$share['created_at'] > $lifetime / 2
                    && filter_var($share['sender_email'] ?? '', FILTER_VALIDATE_EMAIL)
                    && $this->email->sendExpiryReminder($share)) {
                    $this->shares->updateShare($hash, ['warning_sent' => true]);
                    return ['warned' => 1];
                }
                return [];
            });
            foreach ($result as $key => $count) $stats[$key] += $count;
        }
        return $stats;
    }
}
