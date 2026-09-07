<?php

namespace Tests\Unit;

use App\Services\DownloadHeaders;
use CodeIgniter\Test\CIUnitTestCase;

class DownloadHeadersTest extends CIUnitTestCase
{
    public function testOnlyVerifiedSafeMediaCanBeDisplayedInline(): void
    {
        $this->assertTrue(DownloadHeaders::isSafeInline('photo.jpg', 'image/jpeg'));
        $this->assertTrue(DownloadHeaders::isSafeInline('clip.mp4', 'video/mp4; charset=binary'));
        $this->assertFalse(DownloadHeaders::isSafeInline('payload.svg', 'image/svg+xml'));
        $this->assertFalse(DownloadHeaders::isSafeInline('document.pdf', 'application/pdf'));
        $this->assertFalse(DownloadHeaders::isSafeInline('photo.jpg', 'text/html'));
    }
}
