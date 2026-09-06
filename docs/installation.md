# Installation Guide

eXtplorer 3 is a standalone web application designed for easy deployment.

## 1. Server Requirements

Ensure your server meets the following criteria:

* **OS:** Linux (recommended), Windows, or macOS.
* **Web Server:** Apache or Nginx.
* **PHP:** Version **8.2** or higher.
* **PHP Extensions:** `intl`, `mbstring`, `json`, `xml`, `curl`, `gd`, and `zip`.
* **Optional Extensions:** `ftp` for FTP mounts and `ssh2` for SFTP mounts.
* **Encryption key:** Set `EXTPLORER_ENCRYPTION_KEY_FILE` (recommended) or `EXTPLORER_ENCRYPTION_KEY` to store remote
  mount credentials securely.

## 2. Installation Steps

### Step 1: Download and extract

Download the latest release (`.tar.gz` or `.zip`) from the [Releases Page](https://github.com/soerennb/extplorer/releases)
and extract it to the document root (for example, `/var/www/html/extplorer`). Configure the web server document root as the
application's `public/` directory.

### Step 2: Permissions

The application requires write access to `writable/` and its subdirectories. Keep the code and writable data separate when
possible:

```bash
cd /path/to/extplorer
chown -R www-data:www-data writable
find writable -type d -exec chmod 0750 {} \;
find writable -type f -exec chmod 0640 {} \;
```

Replace `www-data` with the web-server user used by the installation.

### Step 3: Migrate and initialize

Run the versioned migration once from the application root. It converts legacy root-level JSON/PHP state into protected files
below `writable/config/` and creates a backup below `writable/backups/` before removing a migrated legacy file:

```bash
php spark security:migrate
```

Migration errors return a non-zero exit code and must be fixed before serving the application.

## 3. Initial Admin Setup

There are no built-in production credentials. On a fresh installation, provide the administrator password through a secret
file and run the bootstrap command:

```bash
umask 077
printf '%s\n' 'choose-a-long-password' > /run/secrets/extplorer-admin-password
EXTPLORER_ADMIN_PASSWORD_FILE=/run/secrets/extplorer-admin-password php spark admin:bootstrap
```

`EXTPLORER_ADMIN_PASS` is accepted for compatibility but should not be used in production. The bootstrap is idempotent: an
existing administrator password is not overwritten on redeploy. For an intentional reset, use the one-shot environment flag
or the CLI command, which never takes the password as a process argument:

```bash
EXTPLORER_ADMIN_PASSWORD_FILE=/run/secrets/extplorer-admin-password \
EXTPLORER_ADMIN_RESET_PASSWORD=1 php spark admin:bootstrap
php spark admin:reset-password admin --password-file /run/secrets/extplorer-admin-password
```

Remove one-shot reset variables after the operation. A missing secret or a persistent user store without an administrator is a
startup error, not a reason to silently create a default account.

## 4. Verification

1. Open the configured HTTPS URL.
2. Log in with the administrator credentials supplied during bootstrap.
3. Enable 2FA and verify the backup/recovery procedure.
4. Confirm that `writable/config/`, `writable/logs/` and `writable/file_manager_root/` are backed up according to your
   recovery policy.

See the [Configuration Guide](configuration.md) for web-server settings and Docker/Dokploy deployment details.
