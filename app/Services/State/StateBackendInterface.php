<?php

namespace App\Services\State;

interface StateBackendInterface
{
    public function read(string $path, array $default = []): array;

    public function write(string $path, array $data): void;

    public function transaction(string $path, callable $callback, array $default = []): mixed;

    public function exists(string $path): bool;

    public function delete(string $path): void;

    /** @param array<string, array> $defaults @return array<string, array> */
    public function readMany(array $defaults): array;

    /** @param array<string, array> $defaults */
    public function transactionMany(array $defaults, callable $callback): mixed;

    public function verify(): void;
}
