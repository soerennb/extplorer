<?php

namespace App\Services;

class SettingsService
{
    private string $settingsFile;

    public function __construct()
    {
        $this->settingsFile = WRITEPATH . 'settings.php';
    }

    /**
     * Reads settings from the PHP-wrapped JSON file.
     */
    public function getSettings(): array
    {
        if (!file_exists($this->settingsFile)) {
            return $this->getDefaultSettings();
        }

        $content = file_get_contents($this->settingsFile);
        // Remove the protection header
        $json = str_replace('<?php die("Access denied"); ?>' . PHP_EOL, '', $content);
        
        $data = json_decode($json, true);
        
        // Merge with defaults to ensure all keys exist
        return array_merge($this->getDefaultSettings(), $data ?? []);
    }

    /**
     * Saves settings to the file with PHP protection.
     */
    public function saveSettings(array $settings): void
    {
        // Sanitize or validate if necessary
        $current = $this->getSettings();
        $newSettings = array_merge($current, $settings);

        $json = json_encode($newSettings, JSON_PRETTY_PRINT);
        $content = '<?php die("Access denied"); ?>' . PHP_EOL . $json;

        file_put_contents($this->settingsFile, $content);
    }

    public function get(string $key, $default = null)
    {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }

    private function getDefaultSettings(): array
    {
        return [
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_crypto' => 'tls', // ssl, tls, null
            'email_from' => 'noreply@example.com',
            'email_from_name' => 'eXtplorer',
            'default_transfer_expiry' => 7,
            'allow_public_uploads' => false,
        ];
    }
}
