# Configuration Guide

eXtplorer 3 is highly configurable. This guide covers environment settings and detailed web server configurations.

## 1. Environment Variables (.env)

Copy the `env` file to `.env` to start customizing:
```bash
cp env .env
```

Set `EXTPLORER_BASE_URL` to the public HTTPS URL before starting a production
installation when you need to pin a public hostname, path or nonstandard port.
Native Apache and Nginx/PHP-FPM installs may omit it when the web server passes
a valid canonical `SERVER_NAME`; see [Canonical URLs for native web servers]
below. Production URLs still require HTTPS, either directly or through a
configured trusted proxy. Plain HTTP is accepted only for loopback URLs
(`localhost`, `127.0.0.1` or `::1`) used by local container tests; CLI
maintenance commands do not require a web URL. Docker Compose continues to
require `EXTPLORER_BASE_URL` explicitly.

### Essential Settings
| Variable | Description | Recommended (Prod) |
| :--- | :--- | :--- |
| `CI_ENVIRONMENT` | Application mode. | `production` |
| `EXTPLORER_BASE_URL` | Full public URL (with trailing slash); optional for native installs with a canonical vhost name, required by Docker Compose. | `https://yourdomain.com/` |
| `EXTPLORER_WRITE_PATH` | Persistent writable root. | `/var/lib/extplorer/writable` |
| `EXTPLORER_FILE_MANAGER_ROOT` | Optional local file root; must stay outside the public webroot and application source tree. | `/var/lib/extplorer/writable/file_manager_root` |
| `EXTPLORER_ENCRYPTION_KEY_FILE` | File containing the encryption key. | `/run/secrets/extplorer-encryption-key` |
| `EXTPLORER_PUBLIC_MOUNT` | Show `<write path>/public` as a common read-write `Public` mount to every account. Off unless set; prefer `Shared`. | unset |
| `app.forceGlobalSecureRequests` | Force HTTPS redirection. | `true` |

### Canonical URLs for native web servers

When `EXTPLORER_BASE_URL` is unset, production PHP-FPM uses the web server's
`SERVER_NAME`, never the request's `HTTP_HOST`. It reads the scheme from the
direct HTTPS server variables or from `X-Forwarded-Proto` only when
`REMOTE_ADDR` matches an address in `EXTPLORER_TRUSTED_PROXY_IPS`. Forwarded
host values are not used. The script directory is included when the server
reports the front controller under a subdirectory.

Configure a concrete canonical vhost name. With Apache, set the same
`ServerName` as the public hostname and enable `UseCanonicalName On`; this
prevents Apache from deriving `SERVER_NAME` from the incoming Host header. With
Nginx, use an exact `server_name` and pass `SERVER_NAME` from `$server_name`
through FastCGI parameters (the standard `fastcgi_params`/`fastcgi.conf` files
usually do this). Do not pass `HTTP_HOST` as `SERVER_NAME`.

For TLS terminated by a reverse proxy, set `EXTPLORER_TRUSTED_PROXY_IPS` to
the concrete proxy address or narrow CIDR and have that proxy overwrite
`X-Forwarded-Proto`. Keep `SERVER_NAME` set to the public vhost name. If the
server cannot provide a valid canonical name, the path differs from the
front-controller directory, or it cannot report the external nonstandard
port, set `EXTPLORER_BASE_URL` explicitly. A missing or wildcard `SERVER_NAME`
fails closed for production web requests.

### Subdirectory and index.php installations

When the installation prefix matches the directory containing the front
controller, native Apache/Nginx can derive it from `SCRIPT_NAME`. Otherwise,
set `EXTPLORER_BASE_URL` to the externally visible application directory,
including its installation prefix and a trailing slash. Keep `index.php` out
of that URL; configure `app.indexPage` separately:

```dotenv
EXTPLORER_BASE_URL=https://files.example.com/extplorer/public/
app.indexPage=''
```

Use an empty `app.indexPage` for rewritten controller URLs. For explicit
front-controller URLs, set `app.indexPage=index.php`; the settings endpoint
can then be reached at
`https://files.example.com/extplorer/public/index.php/api/settings`.
The browser also constructs API URLs directly under `EXTPLORER_BASE_URL`,
so the web server must continue forwarding those requests to the front
controller when an index page is configured.
At the domain root, use `EXTPLORER_BASE_URL=https://files.example.com/` with
the same index-page choices.

API and public-share responses return the current CSRF token in
`X-CSRF-HASH` for all these URL layouts. The browser sends it as
`X-CSRF-TOKEN` on the next mutation. This also applies to the `428` response
that requests password confirmation before saving administrator settings.
Missing this refreshed token can make confirmation fail with `403` and the
generic permission error even for an administrator (issue #60).
Keep token randomization and regeneration enabled; the native release
[routing checks](../tests/e2e/README.md#native-release-routing-checks) verify
the supported URL layouts.

### Storage backends

The default `file` state/session/cache configuration is intended for a single
classic Apache or Nginx/PHP-FPM installation. It requires no database or Redis
service. The following alternatives are available:

| Variable | Values | Use |
| :--- | :--- | :--- |
| `EXTPLORER_STATE_DRIVER` | `file`, `sqlite`, `database` | Metadata and application state |
| `EXTPLORER_SESSION_DRIVER` | `file`, `database`, `redis` | PHP sessions |
| `EXTPLORER_CACHE_DRIVER` | `file`, `redis` in production; `dummy` only in development/testing | Cache and request throttling |

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

Resumable uploads and internal transfer staging also use owner-scoped aggregate
reservations. `EXTPLORER_UPLOAD_STAGING_MAX_MB` (default `2048`),
`EXTPLORER_UPLOAD_STAGING_MAX_FILES` (default `1000`) and
`EXTPLORER_UPLOAD_STAGING_TTL_SECONDS` (default `86400`, minimum five minutes)
bound abandoned or concurrent staging data. The scheduled `shares:cleanup`
command removes expired reservations and transfer directories; run it regularly
in every deployment.

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

Authenticated uploads support local storage, saved FTP/FTPS/SFTP mounts and direct
remote sessions, including resumable chunks. FTP remains available only in the
configured compatibility mode. FTPS also requires explicit compatibility mode
and does not authenticate its actual login connection. It uses explicit TLS (`AUTH TLS`), normally on
port 21; implicit TLS on port 990 is not supported by PHP's FTP extension. The
container verifies that its FTP extension includes SSL support during the build.

Pending uploads appear as **Waiting for virus scan**. Before activation, the worker
rechecks the current endpoint allowlist, account permissions, mount namespace,
filename policy, quota and payload checksum. A failed activation retains pending
bytes for retry. Direct remote upload credentials are encrypted separately, expire
with the scan deadline and are released when the scan finishes. Scanner workers
must use the same writable state and encryption key as the application. Local-only
trash and version history remain unavailable on remote storage.

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
`ghcr.io/soerennb/extplorer3:3.0.0` or
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

#### Mail configuration and upgrades from 3.1.1

Sendmail's executable is an operator-only setting. Set
`EXTPLORER_SENDMAIL_PATH` in the PHP worker environment (Compose already
passes it through), or use the default `/usr/sbin/sendmail`. Its value is
never imported from web settings. The settings page displays the effective
operator path but cannot change it; API writes to `sendmail_path` return 422.

The program must be a regular executable outside managed storage. The PHP
runtime must not own or be able to modify the executable, its directories,
or any intermediate symlink target. Group/world-writable components are
rejected. System-managed symlinks are supported. Sendmail requires a
non-root POSIX runtime with the POSIX extension; run scheduled mail commands
as the same unprivileged application account. SMTP and PHP `mail()` remain
available without these Sendmail filesystem requirements.

If a previous installation saved a custom Sendmail path, delivery remains
disabled until the operator explicitly configures the trusted program in
`EXTPLORER_SENDMAIL_PATH`, or selects and configures another mail transport.
An old saved path is never executed or automatically promoted to operator
configuration. The new mail verification fingerprint invalidates earlier
delivery confirmations; save the settings and send a successful test again.

Mail settings require `admin_settings` (or `*`); `admin_users` no longer
implicitly grants that access. Custom roles that intentionally manage both
areas need both permissions. Mail test and validation calls require the
existing ten-minute step-up grant and share a limit of five calls per minute
per account. Both use saved settings only: `POST api/settings/test-email`
accepts `{ "email": "recipient@example.com" }`, and
`POST api/settings/validate-email` accepts `{}`. Save before checking a
configuration. Transport overrides are rejected with 422; credentials and
remote debugging responses are not returned to the browser or audit log.

Delegated user administrators may only grant permissions they already hold.
They cannot reset, disable, demote or otherwise modify stronger accounts,
change stronger roles/groups, or manage accounts outside their own home
subtree. New and existing target accounts must have extension policies at
least as restrictive as the delegate's own policy. Checks use current persisted rights under the authorization-state
lock, including indirect role/group effects. Full `*` administrators retain
complete administration. CLI/bootstrap operations remain operator actions.

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
- Sensitive administration mutations use step-up authentication. After the
  current password is confirmed, the same authenticated session may perform
  the existing protected user, role, group, settings and mount changes for
  10 minutes without another prompt. The grant requires both the session and
  the token returned by re-authentication, which the page keeps only in memory
  (never in cookies or browser storage); reloading the page asks again. It is
  invalidated by logout, a new login, password/2FA changes or an
  `auth_version` change. Enrolling two-factor authentication also requires it.
- Password guessing is limited per IP (`5/minute`), per username (`5/5 minutes`)
  and per account: five failed passwords lock password sign-in for 15 minutes,
  after which a new failure window starts. The lock only protects the
  password. Existing sessions, remembered logins, WebDAV app passwords and
  public shares keep working, and a browser that has completed a sign-in for
  the account (device cookie `extplorer_device`, 180 days) is exempt from the
  account-wide lock and username throttle. Such a device has its own limit of
  five failures per 15 minutes. Use `disabled` to suspend an account.
- Per-account allowed/blocked extensions apply to uploads, WebDAV, editor
  saves, renames, copies, archives, extraction and version restore.
- Upload-mode shares are blind drop boxes: visitors see only their own uploads
  from the current page, never the existing content. Read shares hide dotfiles
  and dot-directories in listings, direct downloads and folder archives; share
  a single file explicitly to publish a hidden file.
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
- All configured writable roots are checked at startup and by
  `php spark storage:check`; they must remain outside `public/`, and local
  mounts may not overlap application source, vendor, tests or managed storage.
- User-introduced filenames are subject to a hard deny-list for executable and
  server-configuration names. This applies to uploads, editor saves, copy/
  move, archive extraction and WebDAV; per-user extension allowlists cannot
  re-enable those names.
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
  requires a pinned host-key fingerprint. FTPS is disabled in strict mode: PHP's FTP extension cannot authenticate
  the actual login connection, and a separate certificate preflight does not
  provide that guarantee. Prefer SFTP with a pinned host key. The explicit
  operator setting `EXTPLORER_REMOTE_SECURITY_MODE=compat` retains legacy
  FTP/FTPS for a documented migration; FTPS in this mode has no authenticated
  login-connection guarantee even when its certificate preflight succeeds.
  The preflight still uses `EXTPLORER_FTPS_CA_FILE` and an optional SPKI pin.
  Do not treat that compatibility mode as strict TLS verification.
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
    
    # Keep SERVER_NAME tied to this configured vhost, not the request Host header.
    UseCanonicalName On

    # Redirect HTTP to the canonical vhost name.
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{SERVER_NAME}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName files.example.com
    DocumentRoot /var/www/html/extplorer3/public
    UseCanonicalName On
    
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
    return 301 https://files.example.com$request_uri;
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
        # The included FastCGI parameters must set SERVER_NAME from $server_name.
        
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
