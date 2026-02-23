<?php

namespace App\Debug;

use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions as ExceptionsConfig;
use Throwable;

class FriendlyExceptionHandler implements ExceptionHandlerInterface
{
    private ExceptionHandler $fallback;

    public function __construct(private readonly ExceptionsConfig $config)
    {
        $this->fallback = new ExceptionHandler($config);
    }

    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        if ($this->shouldReturnJson($request)) {
            $this->sendJsonError($request, $response, $statusCode, $exception, $exitCode);
            return;
        }

        $this->fallback->handle($exception, $request, $response, $statusCode, $exitCode);
    }

    private function shouldReturnJson(RequestInterface $request): bool
    {
        if (! $request instanceof IncomingRequest) {
            return false;
        }

        $accept = strtolower($request->getHeaderLine('Accept'));
        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        $requestedWith = strtolower($request->getHeaderLine('X-Requested-With'));
        $path = trim((string) $request->getPath(), '/');

        return str_contains($accept, 'application/json')
            || str_contains($accept, 'text/json')
            || str_contains($contentType, 'application/json')
            || $requestedWith === 'xmlhttprequest'
            || str_starts_with($path, 'api/');
    }

    private function sendJsonError(
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        Throwable $exception,
        int $exitCode,
    ): void {
        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        try {
            $response->setStatusCode($statusCode);
        } catch (\Throwable) {
            $statusCode = 500;
            $response->setStatusCode($statusCode);
        }

        $message = $this->friendlyMessageFor($statusCode);
        $payload = [
            'status' => $statusCode,
            'error' => $response->getReasonPhrase(),
            'message' => $message,
            'messages' => [
                'error' => $message,
            ],
        ];

        if (ENVIRONMENT === 'development') {
            $payload['debug'] = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }

        $response
            ->setHeader('Content-Type', 'application/json; charset=UTF-8')
            ->setJSON($payload)
            ->send();

        if (ENVIRONMENT !== 'testing') {
            exit($exitCode);
        }
    }

    private function friendlyMessageFor(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'The request could not be processed.',
            401 => 'Please sign in and try again.',
            403 => 'You do not have permission to perform this action.',
            404 => 'The requested resource was not found.',
            409 => 'The request conflicts with the current state.',
            413 => 'The request payload is too large.',
            429 => 'Too many requests. Please try again shortly.',
            default => 'An unexpected server error occurred. Please try again.',
        };
    }
}
