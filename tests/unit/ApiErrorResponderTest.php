<?php

namespace Tests\Unit;

use App\Services\ApiErrorResponder;
use App\Controllers\ApiController;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class ApiControllerErrorHarness extends ApiController
{
    public function exposeFailure(string $message, int $status = 400, ?string $code = null)
    {
        return $this->fail($message, $status, $code);
    }
}

class ApiErrorResponderTest extends CIUnitTestCase
{
    public function testErrorCodesAreStableForHttpStatuses(): void
    {
        $this->assertSame('invalid_request', ApiErrorResponder::code(400));
        $this->assertSame('forbidden', ApiErrorResponder::code(403));
        $this->assertSame('internal_error', ApiErrorResponder::code(500));
        $this->assertSame('upload_failed', ApiErrorResponder::code(500, 'upload_failed'));
    }

    public function testServerMessagesDoNotExposePathsOrConnectionDetails(): void
    {
        $this->assertSame(
            'An unexpected server error occurred. Please try again.',
            ApiErrorResponder::message('Unable to connect to host files.internal.example', 500)
        );
        $this->assertStringNotContainsString(
            '/var/www/html',
            ApiErrorResponder::message('File not found: /var/www/html/writable/private.txt', 400)
        );
    }

    public function testControllerReturnsStableEnvelopeAndRequestId(): void
    {
        $controller = new ApiControllerErrorHarness();
        $controller->initController(Services::request(), Services::response(), Services::logger());

        $response = $controller->exposeFailure('File not found: /var/www/html/writable/private.txt');
        $payload = json_decode($response->getBody(), true);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('invalid_request', $payload['error']);
        $this->assertSame('File not found: [redacted]', $payload['messages']['error']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $payload['request_id']);
        $this->assertSame($payload['request_id'], $response->getHeaderLine('X-Request-ID'));
        $this->assertStringNotContainsString('/var/www/html', $response->getBody());
    }

    public function testKnownPasswordErrorCodeReturnsSafePublicMessage(): void
    {
        $controller = new ApiControllerErrorHarness();
        $controller->initController(Services::request(), Services::response(), Services::logger());

        $response = $controller->exposeFailure('Current password is incorrect', 400, 'current_password_incorrect');
        $payload = json_decode($response->getBody(), true);

        $this->assertSame('current_password_incorrect', $payload['error']);
        $this->assertSame('Current password is incorrect.', $payload['messages']['error']);
    }
}
