<?php

namespace App\Services;

class MountService
{
    private string $mountsFile;

    public function __construct()
    {
        $this->mountsFile = WRITEPATH . 'mounts.php';
        
        // Migration/Init
        if (file_exists(WRITEPATH . 'mounts.json') && !file_exists($this->mountsFile)) {
            $data = json_decode(file_get_contents(WRITEPATH . 'mounts.json'), true) ?? [];
            $this->saveMounts($data);
            unlink(WRITEPATH . 'mounts.json');
        }

        if (!file_exists($this->mountsFile)) {
            $this->saveMounts([]);
        }
    }

    private function getMounts(): array
    {
        if (!file_exists($this->mountsFile)) return [];
        $content = file_get_contents($this->mountsFile);
        if (strpos($content, '<?php') === 0) {
            $content = substr($content, strpos($content, "\n") + 1);
        }
        return json_decode($content, true) ?? [];
    }

    private function saveMounts(array $mounts): void
    {
        $content = '<?php die("Access denied"); ?>' . PHP_EOL . json_encode($mounts, JSON_PRETTY_PRINT);
        file_put_contents($this->mountsFile, $content);
    }

    public function getUserMounts(string $username): array
    {
        $all = $this->getMounts();
        return array_filter($all, fn($m) => $m['user'] === $username);
    }

    public function addMount(string $username, string $name, string $type, array $config): string
    {
        // Permission Check
        if (!can('mount_external') && !can('admin_users')) {
            throw new \Exception("Permission denied: Cannot mount external paths.");
        }

        // Sanitize Name
        $name = preg_replace('/[^a-zA-Z0-9 _-]/', '', $name);
        if (empty($name)) throw new \Exception("Invalid mount name.");

        // Type Validation
        if ($type === 'local') {
            $path = trim($config['path'] ?? '');

            // Auto-convert WSL paths if running on Windows
            if (DIRECTORY_SEPARATOR === '\\' && preg_match('|^/mnt/([a-z])/(.*)|i', $path, $matches)) {
                $path = strtoupper($matches[1]) . ':/' . $matches[2];
                $config['path'] = $path;
            }

            error_log("Checking local mount path: '$path'");
            if (!is_dir($path)) {
                // Should we allow mounting non-existent paths? Maybe creation later? 
                // For now, strict validation.
                throw new \Exception("Local path does not exist or is not readable: $path");
            }
        } elseif ($type === 'ftp') {
            // Future validation
        } else {
            throw new \Exception("Unknown mount type.");
        }

        $id = uniqid('mnt_');
        $mounts = $this->getMounts();
        
        $mounts[$id] = [
            'id' => $id,
            'user' => $username,
            'name' => $name,
            'type' => $type,
            'config' => $config,
            'created_at' => time()
        ];

        $this->saveMounts($mounts);
        return $id;
    }

    public function removeMount(string $id, string $username): bool
    {
        $mounts = $this->getMounts();
        if (!isset($mounts[$id])) return false;

        $mount = $mounts[$id];
        
        // Ownership check (or admin)
        if ($mount['user'] !== $username && !can('admin_users')) {
            throw new \Exception("Permission denied.");
        }

        unset($mounts[$id]);
        $this->saveMounts($mounts);
        return true;
    }
}
