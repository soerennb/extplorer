<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\RememberMeService;
use App\Services\SettingsService;

class Login extends BaseController
{
    private function protectConnectionSecret(string $secret): string
    {
        if ($secret === '') {
            return '';
        }

        try {
            $ciphertext = \Config\Services::encrypter()->encrypt($secret);
            if ((bool)config('Encryption')->rawData) {
                $ciphertext = base64_encode($ciphertext);
            }
            return 'enc:' . $ciphertext;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not secure remote connection credentials.');
        }
    }

    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }
        $userModel = new UserModel();
        $admin = $userModel->getUser('admin');
        $showDefaultCreds = $admin && password_verify('admin', $admin['password_hash']);
        $locale = $this->preferredLoginLocale();
        $translations = $this->loginTranslations($locale);

        return view('login', [
            'show_default_creds' => $showDefaultCreds,
            'expired' => $this->request->getGet('expired') === '1',
            'return_to' => $this->safeReturnPath((string)($this->request->getGet('return') ?? '/')),
            'login_locale' => $locale,
            'login_t' => $translations,
        ]);
    }

    public function auth()
    {
        $loginMessages = $this->loginTranslations($this->preferredLoginLocale());
        $throttler = \Config\Services::throttler();
        if ($throttler->check(md5($this->request->getIPAddress()), 5, 60) === false) {
            return redirect()->back()->with('error', $loginMessages['login_too_many_attempts']);
        }

        $mode = $this->request->getPost('mode') ?? 'local';
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $rememberRequested = $this->request->getPost('remember_me') === '1';
        $returnTo = $this->safeReturnPath((string)($this->request->getPost('return') ?? '/'));

        if ($mode === 'ftp' || $mode === 'sftp') {
            $host = strtolower(trim((string)$this->request->getPost('remote_host')));
            $port = (int)$this->request->getPost('remote_port');
            $username = trim((string)$username);
            $password = (string)$password;

            try {
                $this->validateRemoteConnectionInput($mode, $host, $port, $username, $password);
                $this->assertRemoteHostAllowed($host);
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->with('error', $this->remoteConnectionErrorMessage($e, $loginMessages));
            }
            
            try {
                $this->openRemoteConnection($mode, $host, $username, $password, $port);
                
                session()->regenerate();
                session()->set([
                    'isLoggedIn' => true,
                    'username' => $username,
                    'role' => 'user',
                    'home_dir' => '/',
                    'permissions' => ['read', 'write', 'upload', 'delete', 'chmod'],
                    'connection' => [
                        'mode' => $mode,
                        'host' => $host,
                        'port' => $port,
                        'user' => $username,
                        'pass' => $this->protectConnectionSecret($password)
                    ]
                ]);
                $remember = new RememberMeService();
                $response = redirect()->to($returnTo);
                $remember->forget($this->request, $response);
                return $response;
            } catch (\Exception $e) {
                return redirect()->back()->withInput()->with('error', $this->remoteConnectionErrorMessage($e, $loginMessages));
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
                     // Check recovery codes
                     $validRecovery = false;
                     $recoveryCodes = $userModel->getRecoveryCodes($username);
                     
                     if (in_array($code, $recoveryCodes)) {
                         $validRecovery = true;
                         // Remove used code
                         $recoveryCodes = array_diff($recoveryCodes, [$code]);
                         $userModel->updateUser($username, ['recovery_codes' => array_values($recoveryCodes)]);
                     }
                     
                     if (!$validRecovery) {
                         return redirect()->back()->withInput()->with('2fa_required', true)->with('error', $loginMessages['login_invalid_2fa']);
                     }
                }
            }

            $permissions = $userModel->getPermissions($username);
            session()->regenerate();
            session()->set([
                'isLoggedIn' => true,
                'username' => $user['username'],
                'role' => $user['role'],
                'home_dir' => $user['home_dir'],
                'allowed_extensions' => $user['allowed_extensions'] ?? '',
                'blocked_extensions' => $user['blocked_extensions'] ?? '',
                'permissions' => $permissions,
                'connection' => ['mode' => 'local'],
                'force_password_change' => ($user['username'] === 'admin' && password_verify('admin', $user['password_hash']))
            ]);

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
        $mode = $this->request->getPost('mode') ?? '';
        $host = strtolower(trim((string)$this->request->getPost('remote_host')));
        $port = (int)$this->request->getPost('remote_port');
        $username = trim((string)$this->request->getPost('username'));
        $password = (string)$this->request->getPost('password');

        try {
            $this->validateRemoteConnectionInput($mode, $host, $port, $username, $password);
            $this->assertRemoteHostAllowed($host);
            $this->openRemoteConnection($mode, $host, $username, $password, $port);

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
        $remember = new RememberMeService();
        $response = redirect()->to('/login');
        $remember->forget($this->request, $response);
        session()->destroy();
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
            'login_remote_port',
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
            'login_remote_rejected',
            'login_remote_connect_failed',
            'login_remote_test_failed_generic',
            'login_two_factor_code',
            'login_two_factor_hint',
            'remember_me',
            'remember_me_hint',
            'remember_me_local_only',
            'login_submit',
            'login_default_local',
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

    private function validateRemoteConnectionInput(string $mode, string $host, int $port, string $username, string $password): void
    {
        if (!in_array($mode, ['ftp', 'sftp'], true)) {
            throw new \InvalidArgumentException('Select FTP or SFTP before testing the connection.');
        }

        if ($host === '' || $username === '' || $password === '') {
            throw new \InvalidArgumentException('Remote host, username and password are required.');
        }

        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Port must be between 1 and 65535.');
        }
    }

    private function openRemoteConnection(string $mode, string $host, string $username, string $password, int $port): void
    {
        if ($mode === 'ftp') {
            new \App\Services\VFS\FtpAdapter($host, $username, $password, $port);
            return;
        }

        new \App\Services\VFS\Ssh2Adapter($host, $username, $password, $port);
    }

    private function remoteConnectionErrorMessage(\Throwable $e, ?array $messages = null): string
    {
        $messages ??= $this->loginTranslations($this->preferredLoginLocale());
        $message = $e->getMessage();

        if (str_contains($message, 'Select FTP or SFTP')) {
            return $messages['login_remote_select_protocol'];
        }

        if (str_contains($message, 'Remote host, username and password')) {
            return $messages['login_remote_required'];
        }

        if (str_contains($message, 'Port must be')) {
            return $messages['login_remote_port_range'];
        }

        if (str_contains($message, 'not allowlisted')) {
            return $messages['login_remote_host_not_allowed'];
        }

        if (str_contains($message, 'private or reserved')) {
            return $messages['login_remote_private_host'];
        }

        if (stripos($message, 'login failed') !== false || stripos($message, 'authentication') !== false) {
            return $messages['login_remote_rejected'];
        }

        if (stripos($message, 'connect') !== false || stripos($message, 'Could not') !== false) {
            return $messages['login_remote_connect_failed'];
        }

        return $message ?: $messages['login_remote_test_failed_generic'];
    }

    private function assertRemoteHostAllowed(string $host, ?array $allowlist = null): void
    {
        if ($allowlist === null) {
            $settingsService = new SettingsService();
            $allowlist = array_merge(
                config('App')->mountRemoteHostAllowlist ?? [],
                $settingsService->get('mount_remote_host_allowlist', [])
            );
        }

        $allowlist = array_values(array_filter(array_map(
            static fn($entry) => is_string($entry) ? trim(strtolower($entry)) : '',
            $allowlist
        )));

        if (!empty($allowlist)) {
            if (!$this->hostMatchesAllowlist($host, $allowlist)) {
                throw new \RuntimeException('Remote host is not allowlisted.');
            }
            return;
        }

        if ($this->isPrivateOrReservedHost($host)) {
            throw new \RuntimeException('Remote host resolves to a private or reserved target.');
        }
    }

    private function hostMatchesAllowlist(string $host, array $allowlist): bool
    {
        $hostIps = $this->resolveHostIps($host);
        $isHostIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

        foreach ($allowlist as $entry) {
            if ($entry === '') {
                continue;
            }

            if ($entry === $host) {
                return true;
            }

            if (str_starts_with($entry, '*.')) {
                $suffix = substr($entry, 1);
                if (str_ends_with($host, $suffix)) {
                    return true;
                }
            }

            if (strpos($entry, '/') !== false) {
                foreach ($hostIps as $ip) {
                    if ($this->ipMatchesCidr($ip, $entry)) {
                        return true;
                    }
                }
                continue;
            }

            if ($isHostIp && $entry === $host) {
                return true;
            }

            if (filter_var($entry, FILTER_VALIDATE_IP) !== false && in_array($entry, $hostIps, true)) {
                return true;
            }
        }

        return false;
    }

    private function isPrivateOrReservedHost(string $host): bool
    {
        $ips = $this->resolveHostIps($host);
        if (empty($ips)) {
            return true;
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function resolveHostIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = [];
        $records = dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ips[] = $record['ip'];
                }
                if (isset($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if (empty($ips)) {
            $ipv4 = gethostbynamel($host);
            if (is_array($ipv4)) {
                foreach ($ipv4 as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        if ($ip === '::' || $ip === '::1') {
            return true;
        }

        return $this->ipv6MatchesPrefix($ip, 'fc00::', 7)
            || $this->ipv6MatchesPrefix($ip, 'fe80::', 10)
            || $this->ipv6MatchesPrefix($ip, 'ff00::', 8)
            || $this->ipv6MatchesPrefix($ip, '2001:db8::', 32);
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $network = trim($parts[0]);
        $prefix = (int)trim($parts[1]);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && $prefix >= 0 && $prefix <= 32
        ) {
            $ipLong = ip2long($ip);
            $networkLong = ip2long($network);
            if ($ipLong === false || $networkLong === false) {
                return false;
            }

            $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
            return (($ipLong & $mask) === ($networkLong & $mask));
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && $prefix >= 0 && $prefix <= 128
        ) {
            return $this->ipv6MatchesPrefix($ip, $network, $prefix);
        }

        return false;
    }

    private function ipv6MatchesPrefix(string $ip, string $network, int $prefixLength): bool
    {
        $ipPacked = inet_pton($ip);
        $networkPacked = inet_pton($network);
        if ($ipPacked === false || $networkPacked === false) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0
            && substr($ipPacked, 0, $fullBytes) !== substr($networkPacked, 0, $fullBytes)
        ) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($ipPacked[$fullBytes]) & $mask) === (ord($networkPacked[$fullBytes]) & $mask);
    }
}
