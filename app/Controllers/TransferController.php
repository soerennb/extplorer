<?php

namespace App\Controllers;

use App\Services\EmailService;
use App\Services\ShareService;
use App\Services\TransferPolicy;
use App\Services\TransferService;
use App\Services\TransferStagingService;
use App\Services\VFS\VfsFactory;
use RuntimeException;

class TransferController extends BaseController
{
    use ApiResponseTrait;

    private ShareService $shareService;
    private EmailService $emailService;
    private TransferStagingService $staging;
    private TransferPolicy $policy;

    public function __construct()
    {
        $this->shareService = new ShareService();
        $this->emailService = new EmailService();
        $this->staging = new TransferStagingService();
        $this->policy = new TransferPolicy();
    }

    public function capability()
    {
        if (!can('read')) return $this->failForbidden();
        $status = $this->emailService->deliveryStatus();
        return $this->respond(['available' => $status['ready'], 'reason' => $status['reason'], 'settings_url' => null]);
    }

    public function status()
    {
        if (!can('read')) return $this->failForbidden();
        try {
            $result = $this->staging->status((string)session('username'),
                $this->policy->sessionId((string)$this->request->getGet('sessionId')),
                (string)$this->request->getGet('fileName'));
            return $this->respond($result);
        } catch (\Throwable $exception) { return $this->failure($exception); }
    }

    public function stage()
    {
        if (!can('read')) return $this->failForbidden();
        if (($gate = $this->emailDeliveryGate()) !== null) return $gate;
        try {
            $input = $this->request->getJSON(true) ?? [];
            $paths = $input['paths'] ?? [];
            if (!is_array($paths) || $paths === []) throw new RuntimeException('Missing parameters');
            $result = $this->staging->stage((string)session('username'),
                $this->policy->sessionId((string)($input['sessionId'] ?? '')),
                VfsFactory::createFileSystem((string)session('username'), (array)(session('connection') ?? ['mode' => 'local'])),
                $paths, $this->policy->maxFileBytes());
            return $this->respond($result);
        } catch (\Throwable $exception) { return $this->failure($exception); }
    }

    public function upload()
    {
        if (!can('upload')) return $this->failForbidden();
        if (($gate = $this->emailDeliveryGate()) !== null) return $gate;
        try {
            $file = $this->request->getFile('file');
            if (!$file || !$file->isValid()) throw new RuntimeException('Invalid transfer chunk.');
            $result = $this->staging->upload((string)session('username'),
                $this->policy->sessionId((string)$this->request->getPost('sessionId')),
                (string)$this->request->getPost('fileName'), $file->getTempName(),
                (int)$this->request->getPost('fileOffset'), (int)$this->request->getPost('fileSize'),
                (int)$this->request->getPost('chunkIndex'), (int)$this->request->getPost('totalChunks'), $this->policy->maxFileBytes());
            return $this->respond($result);
        } catch (\Throwable $exception) { return $this->failure($exception); }
    }

    public function send()
    {
        if (!can('read')) return $this->failForbidden();
        if (($gate = $this->emailDeliveryGate()) !== null) return $gate;
        $key = 'transfer-send-' . hash('sha256', (string)session('username')) . '-' . hash('sha256', $this->request->getIPAddress());
        if (\Config\Services::throttler()->check($key, 15, MINUTE) === false) return $this->fail('Too many requests. Please slow down.', 429);
        try {
            $request = $this->policy->request($this->request->getJSON(true) ?? [], session('email'));
            $service = new TransferService($this->shareService, $this->emailService, $this->staging, $this->policy);
            return $this->respond($service->send((string)session('username'), $request));
        } catch (\Throwable $exception) { return $this->failure($exception); }
    }

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

    public function delete($hash)
    {
        if (!can('delete') && !can('admin_users')) return $this->failForbidden();
        $hash = is_scalar($hash) ? (string)$hash : '';
        $share = $this->shareService->getShareRaw($hash);
        if (!$share) return $this->failNotFound();
        if ($share['created_by'] !== session('username') && !can('admin_users')) return $this->failForbidden();
        try {
            (new TransferService($this->shareService, $this->emailService, $this->staging, $this->policy))->delete($share);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $exception) { return $this->failure($exception); }
    }

    private function emailDeliveryGate()
    {
        return $this->emailService->isDeliveryReady() ? null
            : $this->fail('Email delivery must be configured and tested before sending files.', 503);
    }

    private function failure(\Throwable $exception)
    {
        $status = $exception->getCode();
        return $this->fail($exception->getMessage(), $status >= 400 && $status <= 599 ? $status : 400);
    }
}
