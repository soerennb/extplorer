<?php

namespace App\Controllers;

use App\Services\ApiErrorResponder;
use CodeIgniter\API\ResponseTrait;

/**
 * Keeps every JSON controller on the same safe error contract.
 */
trait ApiResponseTrait
{
    use ResponseTrait { fail as protected responseTraitFail; }

    protected function fail($messages, int $status = 400, ?string $code = null, string $customMessage = '')
    {
        $requestId = ApiErrorResponder::requestId();
        $errorCode = ApiErrorResponder::code($status, $code);
        $rawMessage = is_array($messages)
            ? (string)($messages['error'] ?? $messages['message'] ?? reset($messages) ?: '')
            : (string)$messages;
        $logMessage = trim((string)preg_replace('/[\x00-\x1F\x7F]+/', ' ', $rawMessage));

        log_message('error', sprintf(
            'API failure request_id=%s status=%d code=%s message=%s',
            $requestId,
            $status,
            $errorCode,
            $logMessage
        ));

        $publicMessages = [
            'error' => ApiErrorResponder::message($messages, $status),
        ];

        // Preserve intentionally structured, non-exception details for clients
        // that need them (for example, protected-role usage information).
        if (is_array($messages)) {
            if (isset($messages['status']) && is_string($messages['status'])) {
                $publicMessages['status'] = mb_substr($messages['status'], 0, 64);
            }
            if (isset($messages['message']) && is_string($messages['message'])) {
                $publicMessages['message'] = ApiErrorResponder::message($messages['message'], 400);
            }
            if (isset($messages['details']) && is_array($messages['details'])) {
                $publicMessages['details'] = $messages['details'];
            }
        }

        $payload = [
            'status' => $status,
            'error' => $errorCode,
            'messages' => $publicMessages,
            'request_id' => $requestId,
        ];
        if (is_array($messages) && isset($messages['action']) && is_string($messages['action'])) {
            $payload['action'] = $messages['action'];
        }

        return $this->respond($payload, $status, $customMessage)->setHeader('X-Request-ID', $requestId);
    }
}
