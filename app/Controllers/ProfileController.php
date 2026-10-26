<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AuthenticationService;
use App\Services\LogService;
use App\Services\PasswordPolicy;
use App\Services\SessionAccount;
use App\Services\StepUpAuthenticationService;
use App\Services\WebDavCredentialService;

class ProfileController extends BaseController
{
    use ApiResponseTrait;

    private function externalSecurityDenied()
    {
        $user = (new UserModel())->getUser((string)SessionAccount::localUsername());
        return ($user['auth_source'] ?? 'local') === 'external'
            ? $this->fail(lang('ExternalAuth.managed'), 403, 'external_account_managed') : null;
    }

    private function requireStepUp(string $action)
    {
        if ((new StepUpAuthenticationService())->consume($this->request, $action)) {
            return true;
        }
        return $this->fail(['error' => 'Additional authentication is required.', 'action' => $action], 428, 'step_up_required');
    }

    private function webDavSecurityDenied()
    {
        $user = (new UserModel())->getUser((string)SessionAccount::localUsername());
        if (($user['auth_source'] ?? 'local') !== 'external') return null;
        return \App\Services\ExternalAuth\ExternalWebDavPolicy::forUser($user)['enabled']
            ? null : $this->fail(lang('ExternalAuth.webdav_disabled'), 403, 'external_webdav_disabled');
    }

    public function webDavCredentials()
    {
        if (($managed = $this->webDavSecurityDenied()) !== null) return $managed;
        $username = (string)SessionAccount::localUsername();
        if ($username === '') return $this->failForbidden('Not logged in');
        $this->response->setHeader('Cache-Control', 'no-store');
        return $this->respond(['items' => (new WebDavCredentialService())->listForUser($username)]);
    }

    public function createWebDavCredential()
    {
        if (($managed = $this->webDavSecurityDenied()) !== null) return $managed;
        if (($stepUp = $this->requireStepUp('webdav-credential.create')) !== true) return $stepUp;
        $username = (string)SessionAccount::localUsername();
        $json = $this->request->getJSON(true);
        try {
            $created = (new WebDavCredentialService())->create($username, (string)($json['label'] ?? ''));
            LogService::log('Create WebDAV credential', $created['credential']['id']);
            $this->response->setHeader('Cache-Control', 'no-store');
            return $this->respondCreated($created);
        } catch (\InvalidArgumentException $exception) {
            return $this->fail($exception->getMessage(), 422);
        } catch (\RuntimeException $exception) {
            return $this->fail($exception->getMessage(), 409);
        }
    }

    public function deleteWebDavCredential(?string $id = null)
    {
        if (($managed = $this->webDavSecurityDenied()) !== null) return $managed;
        if (($stepUp = $this->requireStepUp('webdav-credential.delete')) !== true) return $stepUp;
        if (!is_string($id) || preg_match('/\A[a-f0-9]{32}\z/', $id) !== 1) return $this->failNotFound();
        $username = (string)SessionAccount::localUsername();
        if (!(new WebDavCredentialService())->delete($username, $id)) return $this->failNotFound();
        LogService::log('Delete WebDAV credential', $id);
        return $this->respondDeleted(['status' => 'success']);
    }

    public function getDetails()
    {
        if (!SessionAccount::isLocal()) {
            // A direct remote login has no local profile. The upload dialog
            // still needs the application-wide filename policy.
            return $this->respond([
                'username' => (string)session('username'),
                'connection_mode' => SessionAccount::connectionMode(),
                '2fa_enabled' => false,
                'allowed_extensions' => '',
                'blocked_extensions' => '',
                'system_blocklist' => ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'pl', 'py', 'rb', 'cgi', 'exe', 'sh', 'bat', 'cmd', 'htaccess', 'htpasswd'],
            ]);
        }

        $username = SessionAccount::localUsername();
        if (!$username) return $this->failForbidden('Not logged in');

        $userModel = new UserModel();
        $user = $userModel->getUser($username);
        
        if (!$user) return $this->failNotFound('User not found');

        // Filter sensitive data
        return $this->respond([
            'username' => $user['username'],
            'role' => $user['role'],
            'auth_source' => $user['auth_source'] ?? 'local',
            'display_name' => $user['display_name'] ?? $user['username'],
            'webdav_enabled' => (new \App\Services\SettingsService())->get('webdav_enabled', true)
                && \App\Services\ExternalAuth\ExternalWebDavPolicy::forUser($user)['enabled'],
            'webdav_username' => $user['username'],
            'home_dir' => $user['home_dir'],
            '2fa_enabled' => $user['2fa_enabled'] ?? false,
            'allowed_extensions' => $user['allowed_extensions'] ?? '',
            'blocked_extensions' => $user['blocked_extensions'] ?? '',
            'system_blocklist' => ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'pl', 'py', 'rb', 'cgi', 'exe', 'sh', 'bat', 'cmd', 'htaccess', 'htpasswd']
        ]);
    }

    public function setup2fa()
    {
        if (($managed = $this->externalSecurityDenied()) !== null) return $managed;
        $username = SessionAccount::localUsername();
        if (!$username) return $this->failForbidden('Not logged in');

        $service = new \App\Services\TwoFactorService();
        $secret = $service->generateSecret();
        $qr = $service->getQrCodeUrl($username, $secret);
        session()->set('pending_2fa', [
            'secret' => $secret,
            'expires_at' => time() + 300,
        ]);

        return $this->respond([
            'secret' => $secret,
            'qr' => $qr
        ]);
    }

    public function enable2fa()
    {
        if (($managed = $this->externalSecurityDenied()) !== null) return $managed;
        $username = SessionAccount::localUsername();
        if (!$username) return $this->failForbidden('Not logged in');
        // Enrollment replaces the second factor and revokes other credentials;
        // a hijacked session must not be able to lock the owner out.
        if (($stepUp = $this->requireStepUp('two-factor.enable')) !== true) return $stepUp;

        $json = $this->request->getJSON();
        $code = trim((string)($json->code ?? ''));
        $pending = session('pending_2fa');
        $secret = is_array($pending) && (int)($pending['expires_at'] ?? 0) > time()
            ? (string)($pending['secret'] ?? '')
            : '';

        if (!$secret || !$code) return $this->fail('Two-factor setup expired. Start setup again.');

        $service = new \App\Services\TwoFactorService();
        if ($service->verifyCode($secret, $code)) {
            $userModel = new UserModel();
            $recoveryCodes = $service->generateRecoveryCodes();
            
            if (!$userModel->updateUser($username, [
                '2fa_secret' => $secret,
                '2fa_enabled' => true,
                'recovery_codes' => $recoveryCodes
            ])) {
                return $this->fail('Failed to enable two-factor authentication');
            }

            session()->remove('pending_2fa');
            $updatedUser = $userModel->getUser($username);
            if ($updatedUser) {
                session()->set('auth_version', (int)$updatedUser['auth_version']);
            }
            StepUpAuthenticationService::clearGrant();
            (new AuthenticationService($userModel))->revokeUserTokens($username);
            (new WebDavCredentialService())->revokeUser($username);
            LogService::log('Enable 2FA', '', 'Authenticator enrollment completed');
            
            return $this->respond([
                'status' => 'success',
                'recovery_codes' => $recoveryCodes
            ]);
        }

        return $this->fail('Invalid verification code');
    }

    public function disable2fa()
    {
        if (($managed = $this->externalSecurityDenied()) !== null) return $managed;
        $username = SessionAccount::localUsername();
        if (!$username) return $this->failForbidden('Not logged in');

        $json = $this->request->getJSON();
        $password = (string)($json->password ?? '');
        $code = trim((string)($json->code ?? ''));

        if ($password === '' && $code === '') {
            return $this->fail('Password or authenticator code is required');
        }

        $userModel = new UserModel();
        $reauthenticated = false;

        if ($password !== '') {
            $reauthenticated = (bool)(new AuthenticationService($userModel))->verifyPassword($this->request, $username, $password);
        }

        if (!$reauthenticated && $code !== '') {
            $secret = $userModel->get2faSecret($username);
            if (!$secret) {
                return $this->fail('Two-factor authentication is not enabled');
            }
            $service = new \App\Services\TwoFactorService();
            $reauthenticated = $service->verifyCode($secret, $code);
        }

        if (!$reauthenticated) {
            return $this->fail('Re-authentication failed');
        }

        if (!$userModel->updateUser($username, [
            '2fa_secret' => null,
            '2fa_enabled' => false,
            'recovery_codes' => []
        ])) {
            return $this->fail('Failed to disable two-factor authentication');
        }

        $updatedUser = $userModel->getUser($username);
        if ($updatedUser) {
            session()->set('auth_version', (int)$updatedUser['auth_version']);
        }
        StepUpAuthenticationService::clearGrant();
        (new AuthenticationService($userModel))->revokeUserTokens($username);
        (new WebDavCredentialService())->revokeUser($username);
        LogService::log('Disable 2FA', '', 'Authenticator enrollment removed');

        return $this->respond(['status' => 'success']);
    }

    public function updatePassword()
    {
        if (($managed = $this->externalSecurityDenied()) !== null) return $managed;
        $json = $this->request->getJSON();
        $password = $json->password ?? '';
        $oldPassword = $json->old_password ?? '';

        if (!$password) return $this->fail('Password required');
        if (!$oldPassword) return $this->fail('Current password required');

        $passwordError = PasswordPolicy::validate((string)$password);
        if ($passwordError !== null) {
            return $this->fail($passwordError);
        }

        $username = SessionAccount::localUsername();
        if (!$username) return $this->failForbidden('Not logged in');

        $userModel = new UserModel();
        if (!(new AuthenticationService($userModel))->verifyPassword($this->request, $username, $oldPassword)) {
            return $this->fail('Current password is incorrect', 400, 'current_password_incorrect');
        }
        if ($userModel->updateUser($username, [
            'password' => $password,
            'must_change_password' => false,
        ])) {
            $updatedUser = $userModel->getUser($username);
            if ($updatedUser) {
                session()->set('auth_version', (int)$updatedUser['auth_version']);
            }
            StepUpAuthenticationService::clearGrant();
            (new AuthenticationService($userModel))->revokeUserTokens($username);
            (new WebDavCredentialService())->revokeUser($username);
            LogService::log('Change password', '', 'Password changed');
            if (session('force_password_change')) {
                session()->remove('force_password_change');
            }
            return $this->respond(['status' => 'success']);
        } else {
            return $this->fail('Failed to update password');
        }
    }
}
