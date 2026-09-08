# Configuration Guide

eXtplorer 3 is highly configurable. This guide covers environment settings and detailed web server configurations.

## 1. Environment Variables (.env)

Copy the `env` file to `.env` to start customizing:
```bash
cp env .env
```

### Essential Settings
| Variable | Description | Recommended (Prod) |
| :--- | :--- | :--- |
| `CI_ENVIRONMENT` | Application mode. | `production` |
| `EXTPLORER_BASE_URL` | Full public URL (with trailing slash). | `https://yourdomain.com/` |
| `EXTPLORER_WRITE_PATH` | Persistent writable root. | `/var/lib/extplorer/writable` |
| `EXTPLORER_ENCRYPTION_KEY_FILE` | File containing the encryption key. | `/run/secrets/extplorer-encryption-key` |
| `app.forceGlobalSecureRequests` | Force HTTPS redirection. | `true` |

### Storage backends

The default `file` state/session/cache configuration is intended for a single
classic Apache or Nginx/PHP-FPM installation. It requires no database or Redis
service. The following alternatives are available:

| Variable | Values | Use |
| :--- | :--- | :--- |
| `EXTPLORER_STATE_DRIVER` | `file`, `sqlite`, `database` | Metadata and application state |
| `EXTPLORER_SESSION_DRIVER` | `file`, `database`, `redis` | PHP sessions |
| `EXTPLORER_CACHE_DRIVER` | `file`, `redis`, `dummy` | Cache and request throttling |

`EXTPLORER_CACHE_PREFIX` optionally overrides the cache namespace. It may
contain only letters, numbers, underscores and hyphens because CodeIgniter
reserves punctuation such as `:` in cache keys. The default is `extplorer_`.

Resource limits are enforced for downloads, archive creation/extraction,
thumbnail decoding and storage backups. Configure them with
`EXTPLORER_MAX_DOWNLOAD_MB`, `EXTPLORER_ARCHIVE_MAX_ENTRIES`,
`EXTPLORER_ARCHIVE_MAX_EXPANDED_MB`, `EXTPLORER_ARCHIVE_MAX_RATIO`,
`EXTPLORER_IMAGE_MAX_MB`, `EXTPLORER_IMAGE_MAX_PIXELS` and
`EXTPLORER_BACKUP_MAX_MB`. Directory listings are capped by
`EXTPLORER_MAX_DIRECTORY_ENTRIES` (default 10,000), editor reads by
`EXTPLORER_MAX_CONTENT_MB` (default 16 MB), and outbound connection setup by
`EXTPLORER_REMOTE_TIMEOUT_SECONDS` (default 15 seconds) and
`EXTPLORER_MAX_OPERATION_SECONDS` (default 120 seconds) bound outbound and
recursive operations. Search results are capped by
`EXTPLORER_MAX_SEARCH_RESULTS` (default 10,000). The defaults are
intentionally finite and should be reviewed together with PHP/Nginx upload
and timeout settings.

WebDAV request depth is limited to one level by default. Configure
`EXTPLORER_WEBDAV_MAX_DEPTH` when clients require a deeper listing, but keep
the value finite; `Depth: infinity` is always rejected to avoid unbounded
recursive requests. Cross-origin `Destination` headers are rejected.

For public deployments, set `EXTPLORER_UPLOAD_SCAN_MODE=external` to keep both
authenticated and public-share uploads out of the managed file tree until an
external scanner approves them. Pending payloads and JSON manifests are stored
in `writable/uploads/quarantine` with mode `0700`. A native worker or separate
container can watch pending manifests and finalize a result without an HTTP
scanner command:

```bash
php spark uploads:scan-result QUARANTINE_ID clean --reason 'clamav: OK'
```

Use `infected`, `error` or `expired` to reject a payload. Quarantine capacity
and retention are bounded by `EXTPLORER_UPLOAD_QUARANTINE_MAX_MB`,
`EXTPLORER_UPLOAD_QUARANTINE_MAX_FILES` and
`EXTPLORER_UPLOAD_QUARANTINE_TTL_SECONDS`. The default is `off` for backward
compatibility. A scanner must treat the payload as untrusted bytes and must
not execute it. WebDAV and direct downloads never expose the quarantine path.

`sqlite` is intended for one application instance. Database-backed sessions
require MySQL/MariaDB or PostgreSQL because CodeIgniter does not provide a
SQLite session handler. Use `database` state with a shared MySQL/MariaDB or
PostgreSQL database for multiple replicas. Redis is
used for sessions and cache; it is not the authoritative store for users,
shares or configuration. Run `php spark storage:check` after changing a
backend. A selected but unavailable backend is a startup error and is never
silently replaced by file or dummy storage.

Database credentials can be supplied with `EXTPLORER_DB_*` variables. The
default charset is `utf8mb4` for MySQL/MariaDB and `utf8` for PostgreSQL or
SQLite; set `EXTPLORER_DB_CHARSET` and `EXTPLORER_DB_COLLATION` only when the
database requires a different, validated identifier. Use
`EXTPLORER_DB_PASSWORD_FILE` and `EXTPLORER_REDIS_PASSWORD_FILE` instead of
putting secrets directly into the environment where possible. SQLite defaults
to `writable/extplorer.sqlite` when `EXTPLORER_STATE_DRIVER=sqlite` and no
database path is provided.

For reproducible deployments, set `EXTPLORER_IMAGE_REF` to an immutable
release tag or digest, for example
`ghcr.io/soerennb/extplorer3:3.0.0-beta.5` or
`ghcr.io/soerennb/extplorer3@sha256:<digest>`. `latest` follows the newest
release that passed the release gates and is therefore intentionally mutable;
`pull_policy: always` only controls when the reference is fetched.

The canonical configuration names are `EXTPLORER_BASE_URL`, `EXTPLORER_WRITE_PATH`, `EXTPLORER_ENCRYPTION_KEY` and
`EXTPLORER_ENCRYPTION_KEY_FILE`. The aliases `app.baseURL`, `app_baseURL`, `WRITEPATH` and `encryption.key` remain supported
for existing installations.

### Admin and upload settings

Use `EXTPLORER_ADMIN_PASSWORD_FILE` for first initialization. `EXTPLORER_ADMIN_PASS` is a compatibility fallback. The
password is read only when the persistent user store is empty; redeploying does not overwrite an existing administrator.
Set `EXTPLORER_ADMIN_RESET_PASSWORD=1` for one intentional reset, then remove the flag. The CLI alternative is:

```bash
php spark admin:reset-password admin --password-file /run/secrets/extplorer-admin-password
```

`EXTPLORER_UPLOAD_MAX_FILE_MB` defaults to 100 and is validated between 1 and 10240 MB. The same value configures Nginx,
PHP-FPM and application-level upload/quota checks. Runtime resource controls are available in Compose through
`EXTPLORER_APP_MEMORY_LIMIT`, `EXTPLORER_APP_CPUS`, `EXTPLORER_MEMORY_LIMIT`, `EXTPLORER_MAX_EXECUTION_TIME` and
`EXTPLORER_MAX_INPUT_TIME`.

Persistent state is stored under `writable/config`, logs under `writable/logs`, and local files under
`writable/file_manager_root`. Back up the complete writable volume, especially `config`, `file_manager_root`, `uploads`,
`trash` and `backups`.

Storage restores require `--force`, a compatible data schema and the same
encryption key that created the backup. Run `php spark storage:verify
BACKUP.zip` before a restore; the restore creates a pre-restore backup and
rolls back staged files if activation fails.

### Security Controls (Current Defaults)
The following controls are enabled in the application and should be considered part of your operational baseline:

- HTTPS redirects and secure cookies are enforced in production mode. When
  Traefik terminates TLS, let Traefik own the redirect, set
  `app.forceGlobalSecureRequests=false` in the Dokploy override, and configure
  `EXTPLORER_TRUSTED_PROXY_IPS`; Nginx forwards the trusted
  `X-Forwarded-Proto` value so CodeIgniter still marks the request as secure.
- Session IDs are regenerated on login and old IDs are destroyed. Set
  `EXTPLORER_SESSION_MATCH_IP=1` only when stable client IPs are guaranteed;
  otherwise normal mobile/proxy IP changes would invalidate sessions.
- CSRF token randomization and regeneration are enabled.
- Public share endpoints have throttling:
  - Share password auth: `10 requests/minute` per `share + IP`
  - Share upload: `30 requests/minute` per `share + IP`
- Transfer send endpoint has throttling:
  - Transfer send: `15 requests/minute` per `user + IP`
- Sensitive security denials and throttling events are written to activity logs.
- External local mounts require an explicit, dedicated path allowlist
  (`mountRootAllowlist` / `mount_root_allowlist`). The general
  `file_manager_root` is never implicitly allowlisted; keep mount roots in a
  separate directory such as `writable/mounts/`.
- Outbound remote connections are denied by default. Enable direct remote
  login explicitly in the administrator settings or with
  `EXTPLORER_REMOTE_LOGIN_ENABLED=1` and configure exact endpoint entries in
  `EXTPLORER_REMOTE_ENDPOINT_ALLOWLIST`, one per
  line, including protocol and port, for example:
  `sftp://files.example.com:22`.
- The same exact endpoint policy applies to direct login, connection tests,
  saved mounts and every subsequent VFS connection. An empty allowlist denies
  all remote endpoints. The old host-only setting
  `mount_remote_host_allowlist` and wildcard/CIDR entries no longer grant
  outbound access; migrate them to exact endpoint entries.
- DNS results are resolved and validated before connecting. Private,
  loopback, link-local, multicast, metadata and other reserved targets are
  blocked. `EXTPLORER_REMOTE_ALLOW_PRIVATE_TARGETS=1` is an explicit
  environment-only escape hatch for controlled internal networks and must be
  paired with a narrow exact allowlist.
- Strict remote security is the default: plain FTP is disabled and SFTP
  requires a pinned host-key fingerprint. FTPS requires a verified TLS
certificate and can additionally use `EXTPLORER_FTPS_CA_FILE` or a
  connection SPKI pin (hex SHA-256 or `sha256/<base64>`). Set
  `EXTPLORER_REMOTE_SECURITY_MODE=compat` only for a documented migration.
- Set `EXTPLORER_REMOTE_TIMEOUT_SECONDS` to bound outbound connection setup
  and use network-level egress filtering as a second enforcement layer.
- Only explicitly configured reverse proxies may supply forwarded headers.
  Set `EXTPLORER_TRUSTED_PROXY_IPS` to concrete proxy IPs/CIDRs; never trust
  forwarded headers from arbitrary clients.
- In production, HTTPS responses include HSTS with
  `EXTPLORER_HSTS_MAX_AGE` (default one year). `EXTPLORER_HSTS_INCLUDE_SUBDOMAINS=1`
  is opt-in because it also affects sibling hostnames.
- In Compose, an unset `EXTPLORER_REMOTE_LOGIN_ENABLED` intentionally leaves
  the persisted administrator setting in control. Set `0` to enforce a
  platform-level deny regardless of the UI setting.

## 2. Web Server Configuration

### Option A: Apache 2.4+
Apache is supported out-of-the-box via the included `.htaccess` files.

#### Requirements
*   `mod_rewrite` enabled (`a2enmod rewrite`).
*   `AllowOverride All` set for the directory.

#### Virtual Host Example
```apache
<VirtualHost *:80>
    ServerName files.example.com
    DocumentRoot /var/www/html/extplorer3/public
    
    # Redirect HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName files.example.com
    DocumentRoot /var/www/html/extplorer3/public
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    # IMPORTANT: Point DocumentRoot to 'public/' for security
    <Directory /var/www/html/extplorer3/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/extplorer-error.log
    CustomLog ${APACHE_LOG_DIR}/extplorer-access.log combined
</VirtualHost>
```

#### Troubleshooting Apache
*   **404 on API calls:** Likely `mod_rewrite` is not active or `AllowOverride` is set to `None`.
*   **Permission Denied:** Check file ownership (`chown www-data:www-data`).

### Option B: Nginx + PHP-FPM
Nginx requires manual configuration as it does not read `.htaccess` files.

#### 1. PHP-FPM Pool Configuration
Ensure your PHP-FPM pool is configured correctly.
*   **File:** `/etc/php/8.1/fpm/pool.d/www.conf` (path varies by OS/Version)
*   **Settings:**
    ```ini
    user = www-data
    group = www-data
    listen = /run/php/php8.1-fpm.sock
    listen.owner = www-data
    listen.group = www-data
    pm = dynamic
    pm.max_children = 10
    ```

#### 2. Nginx Server Block
This configuration assumes you have set the root to the `public/` directory, which is the most secure method.

```nginx
server {
    listen 80;
    server_name files.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name files.example.com;
    
    # SSL Configuration (Adjust paths)
    ssl_certificate /etc/ssl/certs/your_cert.crt;
    ssl_certificate_key /etc/ssl/private/your_key.key;
    ssl_protocols TLSv1.2 TLSv1.3;

    # ROOT DIRECTORY: Point to 'public' folder
    root /var/www/html/extplorer3/public;
    index index.php index.html;

    # Access Logs
    access_log /var/log/nginx/extplorer_access.log;
    error_log /var/log/nginx/extplorer_error.log;

    # 1. Main Application Handling
    location / {
        # Tries to serve file directly, fallback to index.php
        try_files $uri $uri/ /index.php?$args;
    }

    # 2. PHP Handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        
        # Adjust socket path for your PHP version
        fastcgi_pass unix:/run/php/php8.1-fpm.sock; 
        
        # FastCGI Params
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeouts (increase for large file operations/archives)
        fastcgi_read_timeout 300; 
    }

    # 3. Security Hardening
    
    # Deny access to hidden files (e.g., .env, .git)
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Deny access to critical directories if root is misconfigured
    # (Redundant if root is set to /public, but good for safety)
    location ~ ^/(app|writable|vendor|tests|spark|composer\.(json|lock)) {
        deny all;
        return 404;
    }
    
    # 4. Large File Uploads
    # Essential for File Manager functionality
    client_max_body_size 100M; 
}
```

#### Troubleshooting Nginx
*   **"File not found" (404) on PHP files:** Check `fastcgi_param SCRIPT_FILENAME`. It must correctly resolve to the file path on disk.
*   **413 Request Entity Too Large:** Increase `client_max_body_size` in Nginx and `upload_max_filesize` / `post_max_size` in `php.ini`.
*   **504 Gateway Timeout:** Increase `fastcgi_read_timeout` for long operations like creating large ZIP archives.

## 3. PHP Configuration (`php.ini`)

For a File Manager, default PHP settings are often too restrictive.

| Directive | Recommended | Reason |
| :--- | :--- | :--- |
| `memory_limit` | `256M` or `512M` | Handling large images/archives. |
| `upload_max_filesize` | `100M`+ | Allow uploading large files. |
| `post_max_size` | `100M`+ | Must be >= `upload_max_filesize`. |
| `max_execution_time` | `60` or `120` | Prevent timeouts during operations. |
| `max_input_vars` | `3000` | Handling large folder listings in POST. |

After changing these, restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```
