<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('ensure_encryption_key')) {
    function ensure_encryption_key(): void {
        $configuredKey = getenv('EXTPLORER_ENCRYPTION_KEY');
        if ($configuredKey === false || trim($configuredKey) === '') {
            $configuredKey = getenv('encryption.key');
        }

        // Check if key is already set in env or server.
        if ($configuredKey !== false && trim($configuredKey) !== '') {
            putenv('encryption.key=' . trim($configuredKey));
            $_SERVER['encryption.key'] = trim($configuredKey);
            $_ENV['encryption.key'] = trim($configuredKey);
            return;
        }

        $configuredFile = getenv('EXTPLORER_ENCRYPTION_KEY_FILE');
        $keyFile = ($configuredFile !== false && trim($configuredFile) !== '')
            ? trim($configuredFile)
            : WRITEPATH . 'config/encryption.key';
        $legacyKeyFile = WRITEPATH . 'secret.key';

        if (is_file($keyFile)) {
            $key = trim((string) file_get_contents($keyFile));
        } elseif (is_file($legacyKeyFile)) {
            $key = trim((string) file_get_contents($legacyKeyFile));
        } else {
            try {
                $key = 'hex2bin:' . bin2hex(random_bytes(32));
            } catch (Exception $e) {
                throw new RuntimeException('Unable to generate encryption key.', 0, $e);
            }

            $directory = dirname($keyFile);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create encryption key directory.');
            }
            if (file_put_contents($keyFile, $key, LOCK_EX) === false) {
                throw new RuntimeException('Unable to persist encryption key.');
            }
            chmod($keyFile, 0600);
        }

        if ($key === '') {
            throw new RuntimeException('Encryption key file is empty.');
        }

        putenv("encryption.key=$key");
        $_SERVER['encryption.key'] = $key;
        $_ENV['encryption.key'] = $key;
    }
}

ensure_encryption_key();
