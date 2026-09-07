<?php

namespace App\Services;

use CodeIgniter\Encryption\EncrypterInterface;
use RuntimeException;

/**
 * Encrypts remote connection credentials before they enter persistent or
 * session state. Plain values must only exist for the duration of a connect.
 */
final class RemoteCredentialService
{
    public function __construct(private ?EncrypterInterface $encrypter = null)
    {
        $this->encrypter ??= \Config\Services::encrypter();
    }

    public function protect(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            $ciphertext = $this->encrypter->encrypt($value);
            if ((bool) config('Encryption')->rawData) {
                $ciphertext = base64_encode($ciphertext);
            }

            return 'enc:' . $ciphertext;
        } catch (\Throwable $exception) {
            throw new RuntimeException('Could not secure remote connection credentials.', 0, $exception);
        }
    }

    public function reveal(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (!$this->isProtected($value)) {
            return $value;
        }

        $payload = substr($value, 4);
        if ((bool) config('Encryption')->rawData) {
            $decoded = base64_decode($payload, true);
            if ($decoded === false) {
                return '';
            }
            $payload = $decoded;
        }

        try {
            return (string) $this->encrypter->decrypt($payload);
        } catch (\Throwable) {
            return '';
        }
    }

    public function isProtected(string $value): bool
    {
        return str_starts_with($value, 'enc:');
    }
}
