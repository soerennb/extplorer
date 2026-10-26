<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use RuntimeException;

/** Immutable operator configuration, deliberately absent from browser settings. */
final class ExternalAuthConfiguration
{
    private array $configuration;

    public function __construct(?array $configuration = null)
    {
        if ($configuration === null) {
            $file = getenv('EXTPLORER_AUTH_CONFIG_FILE');
            $configuration = [];
            if (is_string($file) && $file !== '') {
                $path = OperatorFile::path($file);
                if (!is_file($path) || filesize($path) > 65536) throw new RuntimeException('Invalid external authentication configuration file.');
                $configuration = json_decode((string)file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
            }
        }
        if (!is_array($configuration) || !is_array($configuration['providers'] ?? [])) {
            throw new RuntimeException('Invalid external authentication configuration.');
        }
        $this->configuration = $configuration;
        foreach ($this->providers() as $id => $provider) $this->validate($id, $provider);
        if ($this->defaultProvider() !== null) $this->provider($this->defaultProvider());
    }

    public function providers(): array
    {
        return $this->configuration['providers'] ?? [];
    }

    public function provider(string $id): array
    {
        $provider = $this->providers()[$id] ?? null;
        if (!is_array($provider)) throw new RuntimeException('Unknown external authentication provider.');
        return $provider + ['label' => $id, 'session_ttl_seconds' => 3600, 'home_prefix' => 'external', 'group_claims' => ['groups']];
    }

    public function defaultProvider(): ?string
    {
        $value = $this->configuration['default_provider'] ?? null;
        if ($value !== null && !is_string($value)) throw new RuntimeException('Invalid default authentication provider.');
        return $value;
    }

    public function fingerprint(string $id): string
    {
        return hash('sha256', json_encode([$this->provider($id), $this->configuration['extension_path'] ?? null], JSON_THROW_ON_ERROR));
    }

    public function createProvider(string $id): ExternalAuthProviderInterface
    {
        $provider = $this->provider($id);
        if ($provider['type'] === 'oidc') return new OidcProvider($provider);
        $directory = OperatorFile::path((string)($this->configuration['extension_path'] ?? ''));
        if (!is_dir($directory)) throw new RuntimeException('Invalid authentication extension directory.');
        // A dedicated namespace prevents extensions shadowing application/framework classes.
        $class = 'ExtplorerAuth\\' . $provider['class'];
        $file = OperatorFile::path($directory . '/' . $provider['class'] . '.php');
        if (!str_starts_with($file, rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Invalid authentication extension path.');
        }
        service('autoloader')->addNamespace('ExtplorerAuth', $directory);
        $instance = new $class($provider);
        if (!$instance instanceof ExternalAuthProviderInterface) throw new RuntimeException('Invalid authentication extension interface.');
        return $instance;
    }

    public function assertReady(): void
    {
        $state = (new \App\Services\AuthorizationState())->snapshot();
        foreach ($this->providers() as $id => $_provider) {
            $provider = $this->provider($id);
            if (!isset($state['roles'][$provider['base_role']])) throw new RuntimeException('External authentication base role does not exist.');
            foreach ($provider['group_map'] as $groups) foreach ($groups as $group) {
                if (!isset($state['groups'][$group])) throw new RuntimeException('External authentication mapped group does not exist.');
            }
            $this->createProvider($id);
        }
    }

    private function validate(mixed $id, mixed $provider): void
    {
        if (!is_string($id) || preg_match('/\A[a-z][a-z0-9_-]{0,31}\z/', $id) !== 1 || !is_array($provider)
            || !in_array($provider['type'] ?? null, ['oidc', 'custom'], true)
            || !is_string($provider['issuer'] ?? null) || $provider['issuer'] === ''
            || !is_string($provider['base_role'] ?? null) || $provider['base_role'] === ''
            || !is_array($provider['group_map'] ?? null) || $provider['group_map'] === []) {
            throw new RuntimeException('Invalid external authentication provider configuration.');
        }
        foreach ($provider['group_map'] as $source => $groups) {
            // JSON object keys such as "42" become integer keys when decoded by PHP.
            if ($source === '' || !is_array($groups) || !array_is_list($groups)
                || array_filter($groups, static fn($group): bool => !is_string($group) || $group === '') !== []) {
                throw new RuntimeException('Invalid external authentication group mapping.');
            }
        }
        $ttl = $provider['session_ttl_seconds'] ?? 3600;
        $prefix = $provider['home_prefix'] ?? 'external';
        if (!is_int($ttl) || $ttl < 60 || $ttl > 86400 || !is_string($prefix)
            || preg_match('/\A[a-zA-Z0-9_-]+(?:\/[a-zA-Z0-9_-]+)*\z/', $prefix) !== 1
            || !is_string($provider['label'] ?? $id) || strlen($provider['label'] ?? $id) > 100) {
            throw new RuntimeException('Invalid external authentication session or home policy.');
        }
        if ($provider['type'] === 'custom') {
            if (!is_string($this->configuration['extension_path'] ?? null) || $this->configuration['extension_path'] === ''
                || !is_string($provider['class'] ?? null) || preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/', $provider['class']) !== 1) {
                throw new RuntimeException('Invalid authentication extension class.');
            }
        } else {
            RestrictedHttpClient::origin($provider['issuer']);
            if (!is_string($provider['client_id'] ?? null) || $provider['client_id'] === ''
                || !is_string($provider['client_secret_file'] ?? null) || $provider['client_secret_file'] === '') {
                throw new RuntimeException('Invalid OIDC client configuration.');
            }
            foreach (['allowed_origins', 'group_claims'] as $field) {
                if (isset($provider[$field]) && (!is_array($provider[$field]) || !array_is_list($provider[$field]))) {
                    throw new RuntimeException('Invalid OIDC list configuration.');
                }
            }
            if ((isset($provider['allow_private_targets']) && !is_bool($provider['allow_private_targets']))
                || (isset($provider['acr_values']) && (!is_string($provider['acr_values']) || preg_match('/\A\S+(?: \S+)*\z/', $provider['acr_values']) !== 1))) {
                throw new RuntimeException('Invalid OIDC security configuration.');
            }
            foreach ($provider['allowed_origins'] ?? [] as $origin) {
                if (!is_string($origin) || RestrictedHttpClient::origin($origin) !== rtrim($origin, '/')) {
                    throw new RuntimeException('OIDC allowed origins must be exact HTTPS origins.');
                }
            }
            foreach ($provider['group_claims'] ?? ['groups'] as $claim) {
                if (!is_string($claim) || preg_match('/\A[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*\z/', $claim) !== 1) {
                    throw new RuntimeException('Invalid OIDC group claim.');
                }
            }
        }
    }
}
