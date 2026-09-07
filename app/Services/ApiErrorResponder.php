<?php

namespace App\Services;

/**
 * Builds the small, stable error envelope used by JSON API endpoints.
 */
final class ApiErrorResponder
{
    public static function requestId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function code(int $status, ?string $code = null): string
    {
        if ($code !== null && preg_match('/\A[a-z][a-z0-9_.-]{1,63}\z/', $code)) {
            return $code;
        }

        return match ($status) {
            401 => 'auth_required',
            403 => 'forbidden',
            404 => 'not_found',
            409 => 'conflict',
            413 => 'payload_too_large',
            422 => 'validation_failed',
            429 => 'rate_limited',
            default => $status >= 500 ? 'internal_error' : 'invalid_request',
        };
    }

    public static function message(mixed $messages, int $status): string
    {
        $message = is_array($messages)
            ? (string)($messages['error'] ?? $messages['message'] ?? reset($messages) ?: '')
            : (string)$messages;
        $message = trim((string)preg_replace('/[\x00-\x1F\x7F]+/', ' ', $message));

        if ($status >= 500 || $message === '' || self::containsSensitiveDetail($message)) {
            return 'An unexpected server error occurred. Please try again.';
        }

        $message = (string)preg_replace(
            '~(?:https?://|ftp://)[^\s"\'<>]+|(?<![A-Za-z0-9])(?:[A-Za-z]:[\\/]|/)[^\s"\'<>]+~i',
            '[redacted]',
            $message
        );

        return mb_substr(trim($message), 0, 240);
    }

    private static function containsSensitiveDetail(string $message): bool
    {
        return preg_match(
            '/\b(?:database|dsn|hostname|host|password|secret|socket|ssh|sftp|ftp|smtp|pdo|sql|connection)\b/i',
            $message
        ) === 1;
    }
}
