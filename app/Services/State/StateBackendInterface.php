<?php

namespace App\Services\State;

interface StateBackendInterface
{
    public function read(string $path, array $default = []): array;

    public function write(string $path, array $data): void;

    public function transaction(string $path, callable $callback, array $default = []): mixed;

    public function exists(string $path): bool;

    public function verify(): void;
}
