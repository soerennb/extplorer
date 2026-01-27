<?php

namespace Tests\Unit;

use App\Controllers\ApiController;
use CodeIgniter\Test\CIUnitTestCase;

class ApiControllerSharePolicyTest extends CIUnitTestCase
{
    private function callPrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    public function testRequireExpiryFallsBackToDefault(): void
    {
        $controller = new ApiController();
        $settings = [
            'share_require_expiry' => true,
            'share_default_expiry_days' => 5,
            'share_max_expiry_days' => 30,
        ];

        $expiresAt = $this->callPrivate($controller, 'normalizeShareExpiry', [null, $settings]);
        $this->assertIsInt($expiresAt);
        $this->assertGreaterThan(time(), $expiresAt);
    }

    public function testExpiryCannotBeInPast(): void
    {
        $controller = new ApiController();
        $settings = [
            'share_require_expiry' => false,
            'share_default_expiry_days' => 7,
            'share_max_expiry_days' => 30,
        ];

        $this->expectException(\Exception::class);
        $this->callPrivate($controller, 'normalizeShareExpiry', [time() - 60, $settings]);
    }

    public function testExpiryCannotExceedMaxPolicy(): void
    {
        $controller = new ApiController();
        $settings = [
            'share_require_expiry' => false,
            'share_default_expiry_days' => 7,
            'share_max_expiry_days' => 2,
        ];

        $tooFar = time() + (5 * 86400);
        $this->expectException(\Exception::class);
        $this->callPrivate($controller, 'normalizeShareExpiry', [$tooFar, $settings]);
    }
}

