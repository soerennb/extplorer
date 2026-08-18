<?php

declare(strict_types=1);

$releaseVersion = $argv[1] ?? '';

if (! preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $releaseVersion)) {
    fwrite(STDERR, "Invalid release version: {$releaseVersion}\n");
    exit(1);
}

$config = file_get_contents(__DIR__ . '/../app/Config/App.php');
$versionPattern = <<<'REGEX'
/public\s+string\s+\$version\s*=\s*'([^']+)';/
REGEX;

if ($config === false || ! preg_match($versionPattern, $config, $matches)) {
    fwrite(STDERR, "Unable to read the application version from app/Config/App.php.\n");
    exit(1);
}

$applicationVersion = $matches[1];

if ($releaseVersion !== $applicationVersion) {
    fwrite(
        STDERR,
        "Release version {$releaseVersion} does not match application version {$applicationVersion}.\n",
    );
    exit(1);
}

fwrite(STDOUT, "Release version {$releaseVersion} matches the application version.\n");
