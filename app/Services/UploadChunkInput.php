<?php

namespace App\Services;

use CodeIgniter\HTTP\IncomingRequest;
use RuntimeException;

/** Accepts native uploaded files, binary PUTs and the legacy multipart PUT contract. */
final class UploadChunkInput
{
    public function store(IncomingRequest $request, UploadSessionService $sessions, string $id, int $index): void
    {
        $file = $request->getFile('file');
        if ($file !== null) {
            if (!$file->isValid()) throw new RuntimeException('Invalid upload chunk.');
            $sessions->storeChunkFromPath($id, $index, $file->getTempName(), (int)$file->getSize());
            return;
        }
        $contentType = $request->getHeaderLine('Content-Type');
        $limit = UploadSessionService::MAX_CHUNK_SIZE + 65536;
        $length = $request->getHeaderLine('Content-Length');
        if ($length !== '' && (!ctype_digit($length) || (int)$length > $limit)) throw new RuntimeException('Upload chunk exceeds the size limit.', 413);
        $body = $request->getBody();
        if (!is_string($body) || $body === 'php://input') {
            $input = fopen('php://input', 'rb');
            if ($input === false) throw new RuntimeException('Unable to open chunk input.');
            try { $body = (new ResourcePolicy())->readStream($input, $limit); }
            finally { fclose($input); }
        }
        if (strlen($body) > $limit) throw new RuntimeException('Upload chunk exceeds the size limit.', 413);
        if (str_starts_with(strtolower($contentType), 'multipart/form-data')) {
            $body = $this->multipartFile($contentType, $body);
        } elseif (!str_starts_with(strtolower($contentType), 'application/octet-stream')) {
            throw new RuntimeException('Invalid upload chunk content type.');
        }
        if (strlen($body) > UploadSessionService::MAX_CHUNK_SIZE) throw new RuntimeException('Upload chunk exceeds the size limit.', 413);
        $path = $sessions->stagingPath($id);
        try {
            if (file_put_contents($path, $body, LOCK_EX) !== strlen($body)) throw new RuntimeException('Unable to stage upload chunk.');
            $sessions->storeChunkFromPath($id, $index, $path, strlen($body));
        } finally { if (is_file($path)) unlink($path); }
    }

    private function multipartFile(string $contentType, string $body): string
    {
        if (preg_match('/\bboundary=(?:"([^"\r\n]{1,70})"|([^;\s]{1,70}))/i', $contentType, $match) !== 1) throw new RuntimeException('Invalid chunk multipart boundary.');
        $boundary = $match[1] !== '' ? $match[1] : $match[2];
        if (!str_starts_with($body, '--' . $boundary . "\r\n") || (!str_ends_with($body, "\r\n--" . $boundary . '--') && !str_ends_with($body, "\r\n--" . $boundary . "--\r\n"))) throw new RuntimeException('Invalid chunk multipart framing.');
        $parts = explode("\r\n--" . $boundary, "\r\n" . $body);
        $payload = null;
        foreach ($parts as $part) {
            if ($part === '' || str_starts_with($part, '--')) continue;
            if (!str_starts_with($part, "\r\n")) throw new RuntimeException('Invalid chunk multipart framing.');
            $separator = strpos($part, "\r\n\r\n");
            if ($separator === false || $separator > 16384) throw new RuntimeException('Invalid chunk multipart headers.');
            $headers = substr($part, 2, $separator - 2);
            if (preg_match('/^Content-Disposition:\s*form-data;[^\r\n]*\bname="file"(?:;|\s|$)/im', $headers) !== 1) continue;
            if ($payload !== null) throw new RuntimeException('Chunk multipart contains multiple files.');
            $payload = substr($part, $separator + 4);
        }
        if ($payload === null) throw new RuntimeException('Chunk multipart file is missing.');
        return $payload;
    }
}
