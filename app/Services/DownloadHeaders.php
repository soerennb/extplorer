<?php

namespace App\Services;

final class DownloadHeaders
{
    /** @var array<string, list<string>> */
    private const INLINE_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'ogv' => ['video/ogg'],
        'mp3' => ['audio/mpeg'],
        'wav' => ['audio/wav', 'audio/x-wav'],
        'ogg' => ['audio/ogg'],
    ];

    public static function isSafeInline(string $filename, string $mime): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = strtolower(trim(explode(';', $mime, 2)[0]));
        return $mime !== '' && in_array($mime, self::INLINE_TYPES[$extension] ?? [], true);
    }

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
