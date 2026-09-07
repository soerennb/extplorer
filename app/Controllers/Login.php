<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\RememberMeService;
use App\Services\AuthenticationService;
use App\Services\LogService;
use App\Services\RemoteEndpointPolicy;

class Login extends BaseController
{
    private function protectConnectionSecret(string $secret): string
    {
        return (new \App\Services\RemoteCredentialService())->protect($secret);
    }

    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }
        $locale = $this->preferredLoginLocale();
        $translations = $this->loginTranslations($locale);
        $remoteLoginEnabled = (new RemoteEndpointPolicy())->directLoginEnabled();

        return view('login', [
            'expired' => $this->request->getGet('expired') === '1',
            'return_to' => $this->safeReturnPath((string)($this->request->getGet('return') ?? '/')),
            'login_locale' => $locale,
            'login_t' => $translations,
            'remote_login_enabled' => $remoteLoginEnabled,
        ]);
    }

    public function auth()
    {
        $loginMessages = $this->loginTranslations($this->preferredLoginLocale());
        $throttler = \Config\Services::throttler();
        $ipKey = 'login_ip_' . hash('sha256', $this->request->getIPAddress());
        if ($throttler->check($ipKey, 5, 60) === false) {
            return redirect()->back()->with('error', $loginMessages['login_too_many_attempts']);
        }

        $modeValue = $this->request->getPost('mode');
        $usernameValue = $this->request->getPost('username');
        $passwordValue = $this->request->getPost('password');
        $mode = is_string($modeValue) ? trim($modeValue) : 'invalid';
        $username = is_string($usernameValue) ? $usernameValue : '';
        $password = is_string($passwordValue) ? $passwordValue : '';
        $rememberRequested = $this->request->getPost('remember_me') === '1';
        $returnTo = $this->safeReturnPath((string)($this->request->getPost('return') ?? '/'));

        $usernameKey = trim((string)$username);
        if ($usernameKey !== '') {
            $userKey = 'login_user_' . hash('sha256', strtolower($usernameKey));
            if ($throttler->check($userKey, 5, 300) === false) {
                return redirect()->back()->with('error', $loginMessages['login_too_many_attempts']);
            }
        }

        if (in_array($mode, ['ftp', 'ftps', 'sftp'], true)) {
            $hostValue = $this->request->getPost('remote_host');
            $portValue = $this->request->getPost('remote_port');
            $fingerprintValue = $this->request->getPost('remote_host_key_fingerprint');
            $tlsPinValue = $this->request->getPost('remote_tls_spki_pin');
            $authMethodValue = $this->request->getPost('remote_auth_method');
            $privateKeyValue = $this->request->getPost('remote_private_key');
            $publicKeyValue = $this->request->getPost('remote_public_key');
            $passphraseValue = $this->request->getPost('remote_private_key_passphrase');
            $host = is_string($hostValue) ? strtolower(trim($hostValue)) : '';
            $port = is_scalar($portValue) ? (int)$portValue : 0;
            $username = trim($username);
            $fingerprint = is_string($fingerprintValue) ? trim($fingerprintValue) : '';
            $tlsPin = is_string($tlsPinValue) ? trim($tlsPinValue) : '';
            $authMethod = is_string($authMethodValue) ? trim($authMethodValue) : 'password';
            $privateKey = is_string($privateKeyValue) ? $privateKeyValue : '';
            $publicKey = is_string($publicKeyValue) ? $publicKeyValue : '';
            $passphrase = is_string($passphraseValue) ? $passphraseValue : '';

            try {
                (new RemoteEndpointPolicy())->assertDirectLoginEnabled();
                $this->validateRemoteConnectionInput($mode, $host, $port, $username, $password, $authMethod, $privateKey, $publicKey);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', $this->remoteConnectionErrorMessage($e, $loginMessages));
            }
            
            try {
                $this->openRemoteConnection($mode, $host, $username, $password, $port, $fingerprint, $tlsPin, $authMethod, $privateKey, $publicKey, $passphrase);
                
                $auth = new AuthenticationService();
                $auth->startRemoteSession($username, [
                    'mode' => $mode,
                    'host' => $host,
                    'port' => $port,
                    'user' => $username,
                    'pass' => $this->protectConnectionSecret($password),
                    'host_key_fingerprint' => $fingerprint,
                    'tls_spki_pin' => $tlsPin,
                    'tls_verified' => $mode === 'ftps',
                    'auth_method' => $authMethod,
                    'private_key' => $this->protectConnectionSecret($privateKey),
                    'public_key' => $this->protectConnectionSecret($publicKey),
                    'private_key_passphrase' => $this->protectConnectionSecret($passphrase),
                    'direct_login' => true,
                ]);
                $remember = new RememberMeService();
                $response = redirect()->to($returnTo);
                $remember->forget($this->request, $response);
                return $response;
            } catch (\Exception $e) {
                LogService::log('Remote Login Failed', '', 'Remote authentication or connection failed.');
                return redirect()->back()->with('error', $this->remoteConnectionErrorMessage($e, $loginMessages));
            }
        }

        $userModel = new UserModel();
        $user = $userModel->verifyUser($username, $password);

        if ($user) {
            // 2FA Check
            if (!empty($user['2fa_enabled'])) {
                $code = $this->request->getPost('2fa_code');
                if (!$code) {
                    return redirect()->back()->withInput()->with('2fa_required', true);
                }
                
                $service = new \App\Services\TwoFactorService();
                $secret = $userModel->get2faSecret($username);
                
                if (!$service->verifyCode($secret, $code)) {
                     $validRecovery = $userModel->consumeRecoveryCode($username, trim((string)$code));
                     if ($validRecovery) {
                         LogService::log('Use recovery code', '', 'Recovery code consumed', $username);
                     }
                     
                     if (!$validRecovery) {
                         return redirect()->back()->withInput()->with('2fa_required', true)->with('error', $loginMessages['login_invalid_2fa']);
                     }
                }
            }

            $user = $userModel->getUser($username) ?? $user;
            (new AuthenticationService($userModel))->startLocalSession($user);

            $remember = new RememberMeService();
            $response = redirect()->to($returnTo);
            if ($rememberRequested) {
                $remember->remember($user['username'], $response);
            } else {
                $remember->forget($this->request, $response);
            }

            return $response;
        } else {
            return redirect()->back()->with('error', $loginMessages['login_invalid_credentials']);
        }
    }

    public function testRemote()
    {
        $loginMessages = $this->loginTranslations($this->preferredLoginLocale());
        $modeValue = $this->request->getPost('mode');
        $hostValue = $this->request->getPost('remote_host');
        $portValue = $this->request->getPost('remote_port');
        $usernameValue = $this->request->getPost('username');
        $passwordValue = $this->request->getPost('password');
        $fingerprintValue = $this->request->getPost('remote_host_key_fingerprint');
        $tlsPinValue = $this->request->getPost('remote_tls_spki_pin');
        $authMethodValue = $this->request->getPost('remote_auth_method');
        $privateKeyValue = $this->request->getPost('remote_private_key');
        $publicKeyValue = $this->request->getPost('remote_public_key');
        $passphraseValue = $this->request->getPost('remote_private_key_passphrase');
        $mode = is_string($modeValue) ? trim($modeValue) : '';
        $host = is_string($hostValue) ? strtolower(trim($hostValue)) : '';
        $port = is_scalar($portValue) ? (int)$portValue : 0;
        $username = is_string($usernameValue) ? trim($usernameValue) : '';
        $password = is_string($passwordValue) ? $passwordValue : '';
        $fingerprint = is_string($fingerprintValue) ? trim($fingerprintValue) : '';
        $tlsPin = is_string($tlsPinValue) ? trim($tlsPinValue) : '';
        $authMethod = is_string($authMethodValue) ? trim($authMethodValue) : 'password';
        $privateKey = is_string($privateKeyValue) ? $privateKeyValue : '';
        $publicKey = is_string($publicKeyValue) ? $publicKeyValue : '';
        $passphrase = is_string($passphraseValue) ? $passphraseValue : '';

        try {
            (new RemoteEndpointPolicy())->assertDirectLoginEnabled();
            $this->validateRemoteConnectionInput($mode, $host, $port, $username, $password, $authMethod, $privateKey, $publicKey);
            $this->openRemoteConnection($mode, $host, $username, $password, $port, $fingerprint, $tlsPin, $authMethod, $privateKey, $publicKey, $passphrase);

            return $this->response->setJSON([
                'ok' => true,
                'message' => $loginMessages['login_remote_success'],
                'csrf' => [
                    'name' => csrf_token(),
                    'hash' => csrf_hash(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'ok' => false,
                    'message' => $this->remoteConnectionErrorMessage($e, $loginMessages),
                    'csrf' => [
                        'name' => csrf_token(),
                        'hash' => csrf_hash(),
                    ],
                ]);
        }
    }

    public function logout()
    {
        $response = redirect()->to('/login');
        (new AuthenticationService())->logout($this->request, $response);
        return $response;
    }

    private function safeReturnPath(string $returnTo): string
    {
        $returnTo = trim($returnTo);
        if ($returnTo === '' || $returnTo[0] !== '/') {
            return '/';
        }

        if (str_starts_with($returnTo, '//') || preg_match('/[\r\n]/', $returnTo)) {
            return '/';
        }

        return $returnTo;
    }

    private function preferredLoginLocale(): string
    {
        $i18n = config('I18n');
        $fallback = $i18n->fallbackLocale ?? 'en';
        $supported = $i18n->supportedLocales();
        $acceptLanguage = (string)$this->request->getHeaderLine('Accept-Language');

        foreach ($this->parseAcceptLanguage($acceptLanguage) as $candidate) {
            if (in_array($candidate, $supported, true)) {
                return $candidate;
            }

            $base = strtolower(strtok($candidate, '-'));
            if ($base !== '' && in_array($base, $supported, true)) {
                return $base;
            }
        }

        return in_array($fallback, $supported, true) ? $fallback : 'en';
    }

    /**
     * @return list<string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $locales = [];
        foreach (explode(',', $header) as $part) {
            $segments = array_map('trim', explode(';', $part));
            $locale = strtolower(str_replace('_', '-', $segments[0] ?? ''));
            if ($locale === '' || !preg_match('/\A[a-z]{2,3}(?:-[a-z0-9]{2,8})?\z/', $locale)) {
                continue;
            }

            $quality = 1.0;
            foreach (array_slice($segments, 1) as $segment) {
                if (str_starts_with($segment, 'q=')) {
                    $quality = max(0.0, min(1.0, (float)substr($segment, 2)));
                }
            }

            $locales[] = ['locale' => $locale, 'quality' => $quality];
        }

        usort($locales, static fn(array $a, array $b): int => $b['quality'] <=> $a['quality']);
        return array_values(array_map(static fn(array $entry): string => $entry['locale'], $locales));
    }

    /**
     * @return array<string, string>
     */
    private function loginTranslations(string $locale): array
    {
        $fallbackMessages = $this->loadLocaleMessages('en');
        $messages = $locale === 'en'
            ? $fallbackMessages
            : array_merge($fallbackMessages, $this->loadLocaleMessages($locale));

        $keys = [
            'app_name',
            'authenticator_code',
            'authenticator_code_hint',
            'login_title',
            'login_too_many_attempts',
            'login_invalid_2fa',
            'login_invalid_credentials',
            'login_welcome_back',
            'login_intro',
            'login_tag_versioned_edits',
            'login_tag_smart_sharing',
            'login_tag_mounts_webdav',
            'login_security_note',
            'login_sign_in',
            'login_subtitle',
            'username',
            'password',
            'login_connection_mode',
            'login_mode_local',
            'login_mode_ftp_hint',
            'login_remote_host',
            'login_remote_host_hint',
            'login_remote_host_placeholder',
            'login_remote_host_key_fingerprint',
            'login_remote_host_key_fingerprint_hint',
            'login_remote_port',
            'login_remote_auth_method',
            'login_remote_auth_password',
            'login_remote_auth_private_key',
            'login_remote_private_key',
            'login_remote_public_key',
            'login_remote_private_key_passphrase',
            'login_remote_tls_spki_pin',
            'login_remote_tls_spki_pin_hint',
            'test_connection',
            'login_remote_test_hint',
            'login_remote_testing',
            'login_remote_test_failed',
            'login_connection_test_failed',
            'login_remote_success',
            'login_remote_select_protocol',
            'login_remote_required',
            'login_remote_port_range',
            'login_remote_host_not_allowed',
            'login_remote_private_host',
            'login_remote_disabled',
            'login_remote_protocol_disabled',
            'login_remote_host_key_fingerprint_required',
            'login_remote_rejected',
            'login_remote_connect_failed',
            'login_remote_test_failed_generic',
            'login_two_factor_code',
            'login_two_factor_hint',
            'remember_me',
            'remember_me_hint',
            'remember_me_local_only',
            'login_submit',
            'login_session_expired_hint',
        ];

        $translations = [];
        foreach ($keys as $key) {
            $translations[$key] = (string)($messages[$key] ?? $key);
        }

        return $translations;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLocaleMessages(string $locale): array
    {
        $messages = [];
        foreach (glob(ROOTPATH . 'resources/i18n/' . $locale . '/*.json') ?: [] as $path) {
            $decoded = json_decode((string)file_get_contents($path), true);
            if (is_array($decoded)) {
                $messages = array_merge($messages, $decoded);
            }
        }

        return $messages;
    }

    private function validateRemoteConnectionInput(
        string $mode,
        string $host,
        int $port,
        string $username,
        string $password,
        string $authMethod = 'password',
        string $privateKey = '',
        string $publicKey = ''
    ): void
    {
        if (!in_array($mode, ['ftp', 'ftps', 'sftp'], true)) {
            throw new \InvalidArgumentException('Select FTP, FTPS or SFTP before testing the connection.');
        }

        if ($host === '' || $username === '') {
            throw new \InvalidArgumentException('Remote host and username are required.');
        }
        if (!in_array($authMethod, ['password', 'private_key'], true)) {
            throw new \InvalidArgumentException('Remote authentication method is invalid.');
        }
        if ($authMethod === 'password' && $password === '') {
            throw new \InvalidArgumentException('Remote password is required.');
        }
        if ($authMethod === 'private_key' && ($mode !== 'sftp' || $privateKey === '' || $publicKey === '')) {
            throw new \InvalidArgumentException('SFTP private and public keys are required.');
        }
        if (strlen($privateKey) > 1024 * 1024 || strlen($publicKey) > 1024 * 1024) {
            throw new \InvalidArgumentException('SFTP key material is too large.');
        }

        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Port must be between 1 and 65535.');
        }
    }

    private function openRemoteConnection(
        string $mode,
        string $host,
        string $username,
        string $password,
        int $port,
        string $fingerprint = '',
        string $tlsPin = '',
        string $authMethod = 'password',
        string $privateKey = '',
        string $publicKey = '',
        string $passphrase = ''
    ): void
    {
        if ($mode === 'ftp' || $mode === 'ftps') {
            new \App\Services\VFS\FtpAdapter($host, $username, $password, $port, '/', $mode === 'ftps', $tlsPin);
            return;
        }

        new \App\Services\VFS\Ssh2Adapter($host, $username, $password, $port, '/', $fingerprint, $authMethod === 'private_key' ? $privateKey : '', $authMethod === 'private_key' ? $publicKey : '', $passphrase);
    }

    private function remoteConnectionErrorMessage(\Throwable $e, ?array $messages = null): string
    {
        $messages ??= $this->loginTranslations($this->preferredLoginLocale());
        $message = $e->getMessage();

        if (str_contains($message, 'Select FTP')) {
            return $messages['login_remote_select_protocol'];
        }

        if (str_contains($message, 'Remote host and username') || str_contains($message, 'Remote password') || str_contains($message, 'private and public keys')) {
            return $messages['login_remote_required'];
        }

        if (str_contains($message, 'Port must be')) {
            return $messages['login_remote_port_range'];
        }

        if (str_contains($message, 'not allowlisted')) {
            return $messages['login_remote_host_not_allowed'];
        }

        if (str_contains($message, 'private') || str_contains($message, 'reserved')) {
            return $messages['login_remote_private_host'];
        }

        if (str_contains($message, 'Direct remote login is disabled')) {
            return $messages['login_remote_disabled'];
        }

        if (str_contains($message, 'FTPS is unavailable') || str_contains($message, 'Plain FTP is disabled')) {
            return $messages['login_remote_protocol_disabled'];
        }

        if (str_contains($message, 'fingerprint')) {
            return $messages['login_remote_host_key_fingerprint_required'];
        }

        if (stripos($message, 'login failed') !== false || stripos($message, 'authentication') !== false) {
            return $messages['login_remote_rejected'];
        }

        if (stripos($message, 'connect') !== false || stripos($message, 'Could not') !== false) {
            return $messages['login_remote_connect_failed'];
        }

        return $messages['login_remote_test_failed_generic'];
    }
}
