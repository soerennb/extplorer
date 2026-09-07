<?php

namespace App\Services;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use RuntimeException;

/**
 * Provides a short-lived, one-time re-authentication proof for sensitive API
 * mutations. The proof is bound to the current user, session and action.
 */
final class StepUpAuthenticationService
{
    public const TTL_SECONDS = 300;

    public function __construct(private ?UserModel $userModel = null)
    {
        $this->userModel ??= new UserModel();
    }

    public function issue(string $action, string $password): string
    {
        $username = (string) session('username');
        if ($username === '' || !session('isLoggedIn')) {
            throw new RuntimeException('Re-authentication is required.');
        }
        if ($password === '' || !$this->userModel->verifyUser($username, $password)) {
            throw new RuntimeException('Re-authentication failed.');
        }

        $token = bin2hex(random_bytes(32));
        $challenges = $this->prune((array) session('step_up_challenges'));
        $challenges[hash('sha256', $token)] = [
            'action' => $action,
            'username' => $username,
            'auth_version' => (int)($this->userModel->getUser($username)['auth_version'] ?? 1),
            'expires_at' => time() + self::TTL_SECONDS,
        ];
        session()->set('step_up_challenges', $challenges);

        return $token;
    }

    public function consume(RequestInterface $request, string $action): bool
    {
        $token = trim($request->getHeaderLine('X-Extplorer-Step-Up'));
        if ($token === '' || !preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
            return false;
        }

        $key = hash('sha256', $token);
        $challenges = $this->prune((array) session('step_up_challenges'));
        $challenge = $challenges[$key] ?? null;
        unset($challenges[$key]);
        session()->set('step_up_challenges', $challenges);

        if (!is_array($challenge)
            || !hash_equals((string)($challenge['action'] ?? ''), $action)
            || !hash_equals((string)($challenge['username'] ?? ''), (string)session('username'))
            || (int)($challenge['expires_at'] ?? 0) < time()) {
            return false;
        }

        $user = $this->userModel->getUser((string)session('username'));
        return is_array($user)
            && empty($user['disabled'])
            && (int)($user['auth_version'] ?? 1) === (int)($challenge['auth_version'] ?? 0);
    }

    /** @return array<string, array<string, mixed>> */
    private function prune(array $challenges): array
    {
        $now = time();
        foreach ($challenges as $key => $challenge) {
            if (!is_array($challenge) || (int)($challenge['expires_at'] ?? 0) < $now) {
                unset($challenges[$key]);
            }
        }

        return $challenges;
    }
}
