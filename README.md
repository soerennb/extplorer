# eXtplorer 3

eXtplorer 3 is a modern, web-based file manager built for speed, security, and ease of deployment. It is the direct successor to the classic eXtplorer 2, completely rewritten from the ground up to meet modern web standards while retaining the powerful features that made the original a favorite for system administrators.

**Note:** Starting with version 3, eXtplorer is only a **standalone application**. It no longer functions as a Joomla! component or integration module, allowing for a cleaner codebase and broader compatibility with any PHP server environment.

## 🚀 Key Features

*   **Modern UI:** A sleek, responsive Single Page Application (SPA) powered by **Vue.js 3** and **Bootstrap 5**.
*   **Virtual File System (VFS):** Manage files on your **Local server**, or connect remotely via **FTP** or **SFTP (SSH)** using the same interface.
*   **Full File Operations:** Create, Rename, Move, Copy, Delete, and Change Permissions (Chmod/Chown) with ease.
*   **Advanced Editor:** Integrated **Ace Editor** with syntax highlighting for 100+ languages.
*   **Visual Assets:** Instant image thumbnails in grid view and a built-in lightbox with navigation.
*   **Diff Viewer:** Compare two files side-by-side to see changes.
*   **Archives:** Create and extract ZIP, TAR, and TAR.GZ archives directly in the browser.
*   **User Management:** Robust Role-Based Access Control (RBAC) with support for Groups and granular permissions.
*   **Keyboard Friendly:** Desktop-like experience with keyboard shortcuts and right-click context menus.
*   **Multilingual:** Full support for English, German, French, and Slovak (i18n ready).
*   **Appearance:** Dark Mode, Light Mode, and automatic System Theme detection.

## 📋 Server Requirements

To run eXtplorer 3, your server must meet the following requirements:

*   **PHP:** Versions 8.2 through 8.5 are supported. The official container uses PHP 8.5.
*   **PHP Extensions:**
    *   `intl` (Required for CodeIgniter 4)
    *   `mbstring`, `json`, `xml`, `curl`
    *   `gd` (Required for image thumbnails)
    *   `zip` (Required for ZIP archive support)
    *   `phar` (Required for TAR/GZ support)
    *   `ftp` (Optional, for FTPS/FTP management; strict mode requires FTPS or SFTP)
    *   `ssh2` (Optional, for SFTP management)
*   **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx.

## 📦 Installation

eXtplorer 3 is designed to be deployed as a single, self-contained bundle.

1.  **Download:** Grab the latest release ZIP from the [GitHub Releases](https://github.com/soerennb/extplorer/releases) page.
2.  **Upload:** Extract the contents to a directory on your web server.
3.  **Permissions:** Ensure the `writable/` directory has write permissions for the web server user.
4.  **Web Server Configuration:**
    *   **Apache:** Works out-of-the-box via the included `.htaccess` files.
    *   **Nginx:** Refer to the included `nginx.conf.example` for configuration.
5.  **Access:** Navigate to the URL in your browser.

## 🐳 Docker (GHCR Images)

Prebuilt images are published to GHCR and intended to run as a multi-container setup with nginx and php-fpm separated.

### Quick Start (Compose)

**Important:** To deploy via Docker, you must have the full repository (including the `docker/` directory) because the `nginx` service mounts the local configuration file.

**Command line:**
```bash
# 1. Clone the repository
git clone https://github.com/soerennb/extplorer3.git
cd extplorer3

# 2. Create a local-only bootstrap secret (use Docker/Dokploy secrets in production)
umask 077
printf '%s\n' 'replace-this-before-starting' > .extplorer-admin-password
export EXTPLORER_ADMIN_PASSWORD_HOST_FILE="$PWD/.extplorer-admin-password"

# 3. Start the stack with Docker Compose secrets
docker compose -f docker-compose.yml -f docker-compose.secrets.yml.example up -d --wait
```

The secrets overlay starts the initialization wrapper as root only long enough to stage
`chmod 600` secret files for the application user; the long-running PHP-FPM process is
still executed as `www-data` (UID 82). Keep the host secret file readable only by root.

**Note for Portainer Users:** Do **not** simply paste the `docker-compose.yml` into the Web Editor. Use the "Repository" method to ensure Portainer clones the configuration files along with the compose file.

- `ghcr.io/soerennb/extplorer3:latest` for the `extplorer-app` service (php-fpm); `latest` tracks the newest release that passed the release gates
- `nginx:alpine` for the `extplorer-web` service
- An `extplorer-init` service that populates the shared code volume on first run and on image updates

### Volumes

- `extplorer_code`: contains immutable versioned application releases and a `current` symlink.
- `extplorer_writable`: contains persistent data only (configuration, users, uploads, logs, sessions, trash and backups).

The application container mounts the code volume read-only and runs PHP-FPM as `www-data` after initialization. Do not
store uploads or application state in the code volume.

### Update Flow

1. Pull the selected image: `docker compose pull`
2. Deploy and wait for `extplorer-init`, `extplorer-app` readiness and `extplorer-web` health: `docker compose up -d --wait`

The init container compares both the image version and a content hash with the active release. A changed image is staged,
syntax-checked and switched atomically; previous releases remain available for rollback. The startup log contains the
release identity. `pull_policy: always` does not replace an already active code release by itself; the `extplorer-init` service performs
that synchronization.

Production deployments should set `EXTPLORER_IMAGE_REF` to an immutable
release tag or digest when reproducibility and controlled rollback matter.
The moving `latest` tag follows the newest release that passed the release
gates and is intended for installations that want automatic beta updates.

To inspect or roll back a release:

```bash
docker compose run --rm extplorer-init --rollback RELEASE_ID
docker compose restart extplorer-app extplorer-web
```

Only roll back to a release that is compatible with the persistent data schema. Every data migration creates a timestamped
backup below `writable/backups/` before changing legacy files.

### Environment Variables (common)

Canonical application settings:
- `CI_ENVIRONMENT`
- `EXTPLORER_BASE_URL`
- `EXTPLORER_WRITE_PATH`
- `EXTPLORER_ENCRYPTION_KEY` or `EXTPLORER_ENCRYPTION_KEY_FILE`

Admin bootstrap (first initialization only):
- `EXTPLORER_ADMIN_USER`
- `EXTPLORER_ADMIN_PASSWORD_FILE` (recommended)
- `EXTPLORER_ADMIN_PASS` (legacy compatibility only)
- `EXTPLORER_ADMIN_RESET_PASSWORD=1` for one explicit, one-shot reset

Settings synced into `writable/config/settings.php` (apply once on first run, or on every start if
`EXTPLORER_APPLY_ENV=1`):
- `EXTPLORER_UPLOAD_MAX_FILE_MB` (default: `100`, maximum: `10240`)
- `EXTPLORER_EMAIL_PROTOCOL`
- `EXTPLORER_SMTP_HOST`
- `EXTPLORER_SMTP_PORT`
- `EXTPLORER_SMTP_USER`
- `EXTPLORER_SMTP_PASSWORD_FILE` (recommended) or `EXTPLORER_SMTP_PASS`
- `EXTPLORER_SMTP_CRYPTO`
- `EXTPLORER_SENDMAIL_PATH`
- `EXTPLORER_EMAIL_FROM`
- `EXTPLORER_EMAIL_FROM_NAME`
- `EXTPLORER_DEFAULT_TRANSFER_EXPIRY`
- `EXTPLORER_ALLOW_PUBLIC_UPLOADS`
- `EXTPLORER_MOUNT_ROOT_ALLOWLIST` (comma- or newline-separated)
- `EXTPLORER_MOUNT_REMOTE_HOST_ALLOWLIST` (comma- or newline-separated)
- `EXTPLORER_REMOTE_SECURITY_MODE=compat|strict` (strict rejects plaintext FTP and requires SFTP host-key fingerprints)
- `EXTPLORER_MAX_SEARCH_RESULTS` and `EXTPLORER_MAX_OPERATION_SECONDS` for bounded recursive operations
- `EXTPLORER_UPLOAD_SCAN_MODE=off|external` for external upload quarantine, plus its quarantine capacity/TTL settings

The old names `app.baseURL`, `app_baseURL`, `WRITEPATH`, `encryption.key` and `EXTPLORER_ADMIN_PASS` remain accepted
as migration aliases. New deployments should use the `EXTPLORER_*` names. Missing bootstrap secrets and migration/configuration
errors make the app container fail; they are never treated as a successful initialization.

### Health and readiness

`GET /health` is served statically by Nginx and does not invoke PHP, the database, a session, DNS or TLS. The `extplorer-app` healthcheck
also requires the readiness marker written only after storage migration, settings synchronization and admin bootstrap have
completed. A healthy Nginx container therefore represents both routing and application readiness.

Share cleanup is an explicit CLI job and is no longer triggered by arbitrary web requests. Run it manually with
`docker compose run --rm --entrypoint php extplorer-app /var/www/html/current/spark shares:cleanup`, or enable the optional worker with
`docker compose --profile worker up -d extplorer-cleanup`.

### Dokploy

Dokploy uses a dedicated Compose override because its Traefik ingress network is external to ordinary Docker:

```bash
docker compose \
  -f docker-compose.yml \
  -f docker-compose.dokploy.yml \
  -f docker-compose.secrets.yml.example \
  up -d --wait
```

Set the Dokploy domain on the `extplorer-web` service in Dokploy's Domains UI and select the external network name used by the
installation (`DOKPLOY_NETWORK_NAME` defaults to `dokploy-network`). Do not publish a host port in Dokploy. See the
[Dokploy deployment runbook](docs/deployment/dokploy.md) for routing reconciliation, file mounts and reload behavior.

## 🛠 Development & Building

If you are contributing to eXtplorer or building from source:

1.  Clone the repository.
2.  Use a supported PHP version (8.2 through 8.5) and Composer 2.10.
3.  Optional for frontend asset maintenance: use Node.js `24.18.0` LTS (`.nvmrc` / `.node-version`).
4.  Install development dependencies: `./composer install`.
5.  After changing translations, run `composer i18n:build` and `composer i18n:check`.
6.  To create a deployable archive, run: `./build.sh`.

Translation contribution details are documented in [docs/translations.md](docs/translations.md).
