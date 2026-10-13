<?php

namespace Tests\Unit;

use App\Services\SettingsInputPolicy;
use App\Services\SettingsService;
use App\Services\SettingsValidationException;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\StorageSandbox;

final class SettingsInputPolicyTest extends CIUnitTestCase
{
    public function testListsMaskedPasswordAndFalseBooleanHaveOneCanonicalInput(): void
    {
        $result = (new SettingsInputPolicy())->normalize(['smtp_pass' => '********', 'webdav_enabled' => 'false',
            'remote_endpoint_allowlist' => ['invalid legacy'], 'remote_endpoint_allowlist_text' => '',
            'share_upload_allowed_extensions_text' => 'TXT,.jpg;txt'], []);
        $this->assertArrayNotHasKey('smtp_pass', $result);
        $this->assertFalse($result['webdav_enabled']);
        $this->assertSame([], $result['remote_endpoint_allowlist']);
        $this->assertSame(['jpg', 'txt'], $result['share_upload_allowed_extensions']);
    }

    public function testChangingMaximumCannotInvalidateTheExistingDefault(): void
    {
        $this->expectException(SettingsValidationException::class);
        $this->expectExceptionMessage('cannot exceed');
        (new SettingsInputPolicy())->normalize(['transfer_max_expiry_days' => 3], ['default_transfer_expiry' => 7]);
    }

    public function testRuntimeDefaultsNormalizeLegacyTypesBeforeReachingTheBrowser(): void
    {
        $sandbox = new StorageSandbox();
        try {
            (new SettingsService())->saveSettings(['log_retention_count' => '3000', 'webdav_enabled' => 'false', 'upload_max_file_mb' => 'invalid']);
            $settings = (new SettingsService())->getSettings();
            $this->assertSame(3000, $settings['log_retention_count']);
            $this->assertFalse($settings['webdav_enabled']);
            $this->assertSame(100, $settings['upload_max_file_mb']);
        } finally { $sandbox->close(); }
    }
}
