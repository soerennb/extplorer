<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use InvalidArgumentException;

final class LogoutRequest
{
    public function __construct(public readonly string $url, public readonly array $parameters)
    {
        RestrictedHttpClient::origin($url);
        if (count($parameters) > 12) throw new InvalidArgumentException('Invalid provider logout parameters.');
        foreach ($parameters as $key => $value) {
            if (!is_string($key) || !is_string($value) || strlen($key) > 64 || strlen($value) > 32768) {
                throw new InvalidArgumentException('Invalid provider logout parameters.');
            }
        }
    }
}
