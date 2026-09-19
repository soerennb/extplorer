<?php

namespace App\Filters;

use App\Services\InstallStateService;
use Config\Services;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class InstallFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Determine if we are currently accessing the install page
        $currentPath = trim($request->getUri()->getPath(), '/');
        // Match install/health as a path segment (works for subfolder deployments)
        $isInstallPage = preg_match('#(^|/)install(/|$)#', $currentPath) === 1;
        $isHealthCheck = preg_match('#(^|/)health(/|$)#', $currentPath) === 1;

        if ($isHealthCheck) {
            return;
        }

        if (!is_writable(config('Storage')->root)) {
            return $isInstallPage ? null : redirect()->to('install');
        }

        // Validate the storage boundary before reading or creating installer
        // state as well. Otherwise an unsafe WRITE_PATH could expose the
        // claim token and newly created account state through the webroot.
        try {
            (new \App\Services\StorageBoundaryPolicy())->assertSafe();
        } catch (\Throwable $exception) {
            log_message('critical', 'Storage boundary validation failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
            return Services::response()
                ->setStatusCode(503)
                ->setHeader('Cache-Control', 'no-store')
                ->setBody('Storage configuration is unsafe.');
        }

        try {
            $status = (new InstallStateService())->status();
        } catch (\Throwable $exception) {
            log_message('critical', 'Unable to determine installation state: {message}', [
                'message' => $exception->getMessage(),
            ]);
            return Services::response()
                ->setStatusCode(503)
                ->setHeader('Cache-Control', 'no-store')
                ->setBody('Installation state is unavailable.');
        }

        if ($status === InstallStateService::STATUS_INSTALLED && $isInstallPage) {
            return redirect()->to('/');
        }

        if ($status !== InstallStateService::STATUS_INSTALLED && !$isInstallPage) {
            return redirect()->to('install');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
