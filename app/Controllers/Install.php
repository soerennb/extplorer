<?php

namespace App\Controllers;

use App\Services\InstallStateService;
use App\Services\LogService;
use Config\Services;
use Throwable;

class Install extends BaseController
{
    private function noStore(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setHeader('Pragma', 'no-cache');
    }

    private function getChecks(): array
    {
        return [
            'php' => [
                'name' => 'PHP Version >= 8.1',
                'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'current' => PHP_VERSION
            ],
            'writable' => [
                'name' => 'Writable Directory Permissions',
                'status' => is_writable(config('Storage')->root),
                'path' => config('Storage')->root
            ],
            'extensions' => [
                'intl' => extension_loaded('intl'),
                'mbstring' => extension_loaded('mbstring'),
                'json' => extension_loaded('json'),
                'gd' => extension_loaded('gd'),
            ],
        ];
    }

    private function checksReady(array $checks): bool
    {
        return $checks['php']['status']
            && $checks['writable']['status']
            && $checks['extensions']['intl']
            && $checks['extensions']['mbstring']
            && $checks['extensions']['json'];
    }

    public function index()
    {
        $this->noStore();
        $state = new InstallStateService();
        $checks = $this->getChecks();

        try {
            $status = $state->status();
        } catch (Throwable $exception) {
            log_message('critical', 'Unable to load installation state: {message}', [
                'message' => $exception->getMessage(),
            ]);
            return view('install/error', ['error' => 'The installation state cannot be read safely.']);
        }

        if ($status === InstallStateService::STATUS_INSTALLED) {
            return redirect()->to('/');
        }

        if ($status === InstallStateService::STATUS_REPAIR) {
            return view('install/error', [
                'error' => 'Persistent application data is incomplete. Use the CLI recovery command before continuing.',
            ]);
        }

        if ($this->checksReady($checks)) {
            try {
                $state->ensureClaimToken();
            } catch (Throwable $exception) {
                log_message('error', 'Unable to prepare installation claim: {message}', [
                    'message' => $exception->getMessage(),
                ]);
                return view('install/error', ['error' => 'The installation claim token could not be prepared safely.']);
            }
        }

        return view('install/index', [
            'checks' => $checks,
            'claimTokenPath' => 'writable/.extplorer-install-token',
            'formReady' => $this->checksReady($checks),
            'username' => 'admin',
        ]);
    }

    public function createAdmin()
    {
        $this->noStore();
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to('install');
        }

        $throttler = Services::throttler();
        $throttleKey = 'install-claim-' . hash('sha256', $this->request->getIPAddress());
        if ($throttler->check($throttleKey, 5, MINUTE) === false) {
            $this->response->setStatusCode(429);
            return view('install/index', [
                'checks' => $this->getChecks(),
                'claimTokenPath' => 'writable/.extplorer-install-token',
                'formReady' => false,
                'username' => trim((string)$this->request->getPost('username')),
                'error' => 'Too many installation attempts. Please try again later.',
            ]);
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        $claimToken = (string)$this->request->getPost('claim_token');
        try {
            (new InstallStateService())->claim($claimToken, $username, $password);
            LogService::log('Installation Claim', '', 'Initial administrator created.', $username);
            return redirect()->to('login')->with('message', 'Installation successful! Please login.');
        } catch (Throwable $exception) {
            log_message('warning', 'Installation claim failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
            LogService::log(
                'Installation Claim Failed',
                '',
                'Invalid or unsuccessful installation claim.',
                $username !== '' ? $username : 'Anonymous'
            );

            return view('install/index', [
                'checks' => $this->getChecks(),
                'claimTokenPath' => 'writable/.extplorer-install-token',
                'formReady' => true,
                'username' => $username,
                'error' => $this->publicClaimError($exception),
            ]);
        }
    }

    private function publicClaimError(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $safePrefixes = [
            'Invalid administrator username.',
            'Password must be at least ',
            'Password must contain a non-whitespace character.',
            'Invalid installation claim token.',
            'Installation claim token is missing or expired.',
            'Installation is not available for a new claim.',
        ];

        foreach ($safePrefixes as $prefix) {
            if (str_starts_with($message, $prefix)) {
                return $message;
            }
        }

        return 'Installation could not be completed safely. Check the server logs.';
    }
}
