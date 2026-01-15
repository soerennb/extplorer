# Installation Guide

eXtplorer 3 is a standalone web application designed for easy deployment.

## 1. Server Requirements

Ensure your server meets the following criteria:

*   **OS:** Linux (Recommended), Windows, or macOS.
*   **Web Server:** Apache or Nginx.
*   **PHP:** Version **8.1** or higher.
*   **PHP Extensions:**
    *   `intl` (Required)
    *   `mbstring`, `json`, `xml`, `curl`
    *   `gd` (Required for thumbnails)
    *   `zip` (Required for Archives)
    *   `ftp` (Optional, for FTP mounts)
    *   `ssh2` (Optional, for SFTP/SSH mounts)

## 2. Installation Steps

### Step 1: Download & Extract
1.  Download the latest release (`.tar.gz` or `.zip`) from the [Releases Page](https://github.com/soerennb/extplorer/releases).
2.  Extract the contents to your web server's document root (e.g., `/var/www/html/extplorer`).

### Step 2: Permissions
The application requires write access to the `writable` directory and its subdirectories.

```bash
cd /path/to/extplorer
chmod -R 0755 writable
chown -R www-data:www-data writable
```
*(Replace `www-data` with your web server's user)*

### Step 3: Web Server Configuration

See the [Configuration Guide](configuration.md) for detailed instructions on setting up Apache or Nginx.

## 3. Initial Admin Setup

Depending on how you obtained eXtplorer 3, it may or may not come with a default administrator account.

*   **Default Credentials:** Try logging in with `admin` / `admin`. 
*   **Manual Setup:** If the default credentials do not work, or if your `writable/users.json` file is empty (`[]`), you must manually create the initial administrator account using the script below.

1.  Create a file named `setup_admin.php` in the root directory (next to `spark`).
2.  Paste the following content into it:

```php
<?php
// setup_admin.php
// Run this via CLI: php setup_admin.php

define('WRITEPATH', __DIR__ . '/writable/');

// 1. Create Default Roles
$roles = [
    'admin' => ['*'], // Admin has ALL permissions
    'user'  => ['read', 'write', 'upload', 'delete', 'rename', 'archive', 'extract']
];

if (!file_exists(WRITEPATH . 'roles.json')) {
    file_put_contents(WRITEPATH . 'roles.json', json_encode($roles, JSON_PRETTY_PRINT));
    echo "[OK] Created writable/roles.json\n";
} else {
    echo "[SKIP] writable/roles.json already exists\n";
}

// 2. Create Admin User
$username = 'admin';
$password = 'admin123'; // CHANGE THIS AFTER LOGIN

$users = [
    [
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
        'home_dir' => '/',
        'groups' => [],
        'allowed_extensions' => '',
        'blocked_extensions' => '',
        '2fa_enabled' => false
    ]
];

if (!file_exists(WRITEPATH . 'users.json') || filesize(WRITEPATH . 'users.json') < 5) {
    file_put_contents(WRITEPATH . 'users.json', json_encode($users, JSON_PRETTY_PRINT));
    echo "[OK] Created writable/users.json\n";
    echo "------------------------------------------------\n";
    echo "User: $username\n";
    echo "Pass: $password\n";
    echo "------------------------------------------------\n";
} else {
    echo "[SKIP] writable/users.json already contains data\n";
}
```

3.  Run the script via CLI:
    ```bash
    php setup_admin.php
    ```
4.  **Delete the script** immediately after use.

## 4. Verification
1.  Open your browser and navigate to your installation (e.g., `http://localhost/extplorer`).
2.  Login with `admin` / `admin123`.
3.  **Immediately change your password** in the Profile section.
