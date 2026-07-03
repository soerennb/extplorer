<?php

namespace Tests\Unit;

use App\Filters\AuthFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;

class AuthFilterSessionExpiryTest extends CIUnitTestCase
{
    public function testExpiredApiSessionReturnsStructuredJsonPayload(): void
    {
        $request = new IncomingRequest(config('App'), new URI('http://example.test/api/ls?path=%2Fdocs'), null, new UserAgent());
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $filter = new AuthFilter();
        $method = new \ReflectionMethod($filter, 'handleExpiredSession');
        $method->setAccessible(true);

        $response = $method->invoke($filter, $request, true);
        $payload = json_decode($response->getBody(), true);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('error', $payload['status'] ?? null);
        $this->assertSame('session_expired', $payload['code'] ?? null);
        $this->assertSame('/api/ls?path=%2Fdocs', $payload['return_url'] ?? null);
        $this->assertStringContainsString('expired=1', $payload['login_url'] ?? '');
    }

    public function testUnauthenticatedApiRequestUsesAuthRequiredCode(): void
    {
        $request = new IncomingRequest(config('App'), new URI('http://example.test/api/ls'), null, new UserAgent());
        $request->setHeader('Accept', 'application/json');

        $filter = new AuthFilter();
        $method = new \ReflectionMethod($filter, 'handleExpiredSession');
        $method->setAccessible(true);

        $response = $method->invoke($filter, $request, false);
        $payload = json_decode($response->getBody(), true);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('auth_required', $payload['code'] ?? null);
        $this->assertStringNotContainsString('expired=1', $payload['login_url'] ?? '');
    }
}
