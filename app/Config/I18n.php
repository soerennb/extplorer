<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class I18n extends BaseConfig
{
    public string $fallbackLocale = 'en';

    /**
     * @return list<array{code: string, labelKey?: string, labelFallback?: string}>
     */
    public function localeOptions(): array
    {
        $path = ROOTPATH . 'resources/i18n/locales.json';
        if (! is_file($path)) {
            $path = FCPATH . 'assets/i18n/locales.json';
        }

        $raw = is_file($path) ? file_get_contents($path) : false;
        if ($raw === false) {
            return [['code' => $this->fallbackLocale]];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [['code' => $this->fallbackLocale]];
        }

        $locales = [];
        foreach ($decoded as $entry) {
            if (! is_array($entry) || empty($entry['code']) || ! is_string($entry['code'])) {
                continue;
            }

            $locales[] = [
                'code' => $entry['code'],
                'labelKey' => is_string($entry['labelKey'] ?? null) ? $entry['labelKey'] : null,
                'labelFallback' => is_string($entry['labelFallback'] ?? null) ? $entry['labelFallback'] : null,
            ];
        }

        return $locales !== [] ? $locales : [['code' => $this->fallbackLocale]];
    }

    /**
     * @return list<string>
     */
    public function supportedLocales(): array
    {
        return array_values(array_unique(array_map(
            static fn (array $entry): string => $entry['code'],
            $this->localeOptions()
        )));
    }
}
