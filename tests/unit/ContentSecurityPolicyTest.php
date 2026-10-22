<?php

namespace Tests\Unit;

use CodeIgniter\HTTP\ContentSecurityPolicy;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\ContentSecurityPolicy as CspConfig;

final class ContentSecurityPolicyTest extends CIUnitTestCase
{
    public function testPolicyKeepsScriptsStrictAndForbidsBaseAndForeignFraming(): void
    {
        $response = new Response(config('App'));
        (new ContentSecurityPolicy(new CspConfig()))->finalize($response);
        $header = $response->getHeaderLine('Content-Security-Policy');

        $this->assertStringContainsString("base-uri 'none'", $header);
        $this->assertStringContainsString("frame-ancestors 'self'", $header);
        $this->assertStringContainsString("form-action 'self'", $header);
        $this->assertDoesNotMatchRegularExpression("/(script|style)-src[^;]*'unsafe-inline'/", $header);
    }
}
