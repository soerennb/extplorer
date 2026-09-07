<?php

namespace App\Services;

final class AdminBootstrapService
{
    public function bootstrapFromEnvironment(): string
    {
        return (new InstallStateService())->bootstrapFromEnvironment();
    }
}
