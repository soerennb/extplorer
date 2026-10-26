<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Router\RouteCollection;

/** CI's wildcard routes are only considered for its standard HTTP verbs. */
final class WebDavRouteCollection extends RouteCollection
{
    public function webdav(string $from, string $to): void
    {
        $this->add($from, $to);
        foreach (['PROPFIND', 'PROPPATCH', 'MKCOL', 'MOVE', 'COPY', 'LOCK', 'UNLOCK'] as $verb) {
            $this->create($verb, $from, $to);
        }
    }
}
