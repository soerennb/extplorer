<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CsrfHeaderFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (!$request instanceof IncomingRequest) {
            return $response;
        }

        // Routing paths exclude the installation directory and index.php.
        $path = trim($request->getPath(), '/');
        if (str_starts_with($path, 'api/') || str_starts_with($path, 's/')) {
            $response->setHeader('X-CSRF-HASH', csrf_hash());
        }

        return $response;
    }
}
