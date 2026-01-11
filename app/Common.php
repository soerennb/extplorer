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
    function ensure_encryption_key() {
        // Check if key is already set in env or server
        if (getenv('encryption.key') || isset($_SERVER['encryption.key'])) {
            return;
        }

        // Check file storage
        $keyFile = WRITEPATH . 'secret.key';
        
        if (file_exists($keyFile)) {
            $key = trim(file_get_contents($keyFile));
        } else {
            // Generate new key
            try {
                $key = 'hex2bin:' . bin2hex(random_bytes(32));
                file_put_contents($keyFile, $key);
                @chmod($keyFile, 0600); // Secure permissions
            } catch (Exception $e) {
                // Fallback or log error? For now, we continue without specific error handling 
                // as the app will likely fail later if key is missing.
                return;
            }
        }

        // Inject into environment
        putenv("encryption.key=$key");
        $_SERVER['encryption.key'] = $key;
        $_ENV['encryption.key'] = $key;
    }
}

ensure_encryption_key();
