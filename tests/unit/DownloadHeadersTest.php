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

    public function testActiveAndExecutableContentIsNeverInline(): void
    {
        foreach ([
            ['payload.svg', 'image/svg+xml'],
            ['page.html', 'text/html'],
            ['document.pdf', 'application/pdf'],
            ['script.js', 'text/javascript'],
            ['binary.exe', 'application/x-dosexec'],
            ['polyglot.jpg', 'text/html'],
            ['unknown.bin', 'application/octet-stream'],
        ] as [$filename, $mime]) {
            $this->assertFalse(
                DownloadHeaders::isSafeInline($filename, $mime),
                $filename . ' must be served as an attachment'
            );
        }
    }

    public function testContentDispositionKeepsArchivesAndUnsafeNamesAsAttachments(): void
    {
        $header = DownloadHeaders::contentDisposition('attachment', "../payload\".svg");

        $this->assertStringStartsWith('attachment;', $header);
        $this->assertStringNotContainsString('../', $header);
        $this->assertStringContainsString('filename*=UTF-8\'\'payload%22.svg', $header);
    }
}
