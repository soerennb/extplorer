<?php

namespace App\Services\State;

use RuntimeException;

final class StateBackendFactory
{
    public static function create(string $driver): ?StateBackendInterface
    {
        return match ($driver) {
            'file' => null,
            'sqlite', 'database' => new DatabaseStateBackend(),
            default => throw new RuntimeException("Unsupported EXTPLORER_STATE_DRIVER: {$driver}"),
        };
    }
}
