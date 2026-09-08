<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

final class AssetVersioningTest extends CIUnitTestCase
{
    public function testI18nScriptUsesApplicationVersionInAllApplicationViews(): void
    {
        $appView = (string) file_get_contents(ROOTPATH . 'app/Views/app.php');
        $adminView = (string) file_get_contents(ROOTPATH . 'app/Views/admin.php');
        $expected = "assets/js/i18n.js?v=' . config('App')->version";

        $this->assertStringContainsString($expected, $appView);
        $this->assertStringContainsString($expected, $adminView);
    }

    public function testApplicationRuntimeDoesNotUsePerRequestTimestampForOwnAssets(): void
    {
        $appView = (string) file_get_contents(ROOTPATH . 'app/Views/app.php');

        $this->assertStringContainsString(
            "assets/js/app.js?v=' . config('App')->version",
            $appView
        );
        $this->assertStringNotContainsString("assets/js/app.js?v=' . time()", $appView);
    }
}
