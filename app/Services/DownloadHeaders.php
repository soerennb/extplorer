<?php

namespace App\Services;

final class DownloadHeaders
{
    public static function filename(string $filename, string $fallback = 'download'): string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = (string)preg_replace('/[\x00-\x1F\x7F\x80-\x9F]/u', '_', $filename);
        $filename = trim($filename, " .\t\n\r\0\x0B");

        return $filename !== '' && $filename !== '.' && $filename !== '..' ? $filename : $fallback;
    }

    public static function contentDisposition(string $disposition, string $filename): string
    {
        $filename = self::filename($filename);
        $ascii = (string)preg_replace('/[^\x20-\x7E]/', '_', $filename);
        $ascii = str_replace(['\\', '"'], ['_', '\\"'], $ascii);

        return sprintf(
            '%s; filename="%s"; filename*=UTF-8\'\'%s',
            $disposition,
            $ascii,
            rawurlencode($filename)
        );
    }
}
