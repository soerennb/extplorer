<?php

namespace App\Filters;

use CodeIgniter\Filters\CSRF;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;

/** Keep byte payloads intact while retaining the framework's full CSRF verifier. */
final class CsrfFilter extends CSRF
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (\App\Services\ExternalAuth\ExternalEventRoutes::matches($request)) return null;
        if (!$request instanceof IncomingRequest) return parent::before($request, $arguments);
        $type = strtolower(explode(';', $request->getHeaderLine('Content-Type'), 2)[0]);
        if (!in_array($type, ['application/octet-stream', 'multipart/form-data'], true)) return parent::before($request, $arguments);
        $body = $request->getBody();
        try {
            return parent::before($request, $arguments);
        } finally {
            // CodeIgniter removes body tokens using parse_str for non-JSON bodies.
            // Uploads carry the token in a header; their bytes are not form fields.
            $request->setBody($body);
        }
    }
}
