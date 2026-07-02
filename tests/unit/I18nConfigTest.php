<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

class I18nConfigTest extends CIUnitTestCase
{
    public function testSupportedLocalesComeFromManifest(): void
    {
        $config = config('I18n');

        $this->assertSame(['en', 'de', 'fr', 'sk'], $config->supportedLocales());
    }
}
