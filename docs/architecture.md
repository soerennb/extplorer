# Architecture Overview

## 1. Technology Stack

* **Backend:** PHP 8.2+ with CodeIgniter 4 Framework.
* **Frontend:** Vue.js 3 and Bootstrap 5.
* **Data storage:** Atomic PHP-wrapped JSON state files. No SQL database is required.

## 2. Persistent Storage Layout

The writable volume is deliberately split so state, user data and operational files can be backed up and permissioned
independently:

| Path | Purpose |
| --- | --- |
| `config/` | users, roles, groups, settings, mounts and remember-me tokens |
| `logs/` | activity logs |
| `file_manager_root/` | local file-manager data; it is not a local-mount allowlist by default |
| `uploads/` | temporary, share and chunk upload data |
| `session/` | PHP sessions |
| `trash/` | per-user trash and indexes |
| `runtime/` | cleanup markers and runtime metadata |
| `backups/` | pre-migration state backups |

State files are written through an atomic temporary-file-and-rename operation and are protected from direct web reads by a
PHP access-denied header. The web server's document root is limited to `public/`.

Legacy root-level JSON/PHP files are converted by the versioned `security:migrate` command. Each migration creates a backup,
validates the target and removes the legacy source only after the target has been read back successfully.

## 3. Virtual File System (VFS)

The VFS abstraction decouples the UI from physical storage.

* **Adapters:** `LocalAdapter`, `FtpAdapter`, and `Ssh2Adapter`.
* **Jails:** Local users are jailed to `file_manager_root` or a configured subdirectory. Path traversal attempts are blocked
  by the VFS layer.
* **Outbound policy:** Remote login, remote connection tests, saved mounts and
  VFS adapters share one exact `protocol://host:port` allowlist. DNS results
  are pinned for the connection and reserved/private targets are denied by
  default.
* **WebDAV:** The endpoint has an explicit method allowlist and rejects
  symlinks to keep filesystem operations inside the authenticated home.

## 4. Security Model

* **Authentication:** Session-based, with optional remember-me tokens.
* **Authorization:** Role-Based Access Control with granular permissions and groups.
* **Secrets:** Encryption keys and bootstrap/SMTP passwords support secret files; legacy environment aliases remain available
  for compatibility.
* **Initialization:** Migrations, settings synchronization and admin bootstrap are strict phases. A readiness marker is written
  only after all phases succeed.
* **Health:** `GET /health` is a static Nginx response and does not invoke PHP, sessions, storage state, DNS or TLS.
* **Uploads:** The default limit is 100 MB and is applied consistently to Nginx, PHP and application checks.

## 5. Container Lifecycle

The Docker deployment uses three services:

1. `extplorer-init` stages an immutable image release into the code volume, validates it, and atomically updates `current`.
2. `extplorer-app` runs migrations, applies first-run configuration, bootstraps the administrator and then starts PHP-FPM as
   `www-data`.
3. `extplorer-web` renders the Nginx configuration, validates it with `nginx -t`, exposes the independent health endpoint and proxies
   requests to PHP-FPM.

The runtime `extplorer-app` runs as `www-data` with a read-only root filesystem. Only the
persistent writable volume and a temporary PHP configuration directory are
writable. The `extplorer-init` service is the root-only permission and code-release
boundary. `system:readiness` verifies the active release and configured
storage backend before the app healthcheck reports readiness.

The code volume is read-only in `extplorer-app` and `extplorer-web`; persistent application data is kept in the separate writable volume.
Previous code releases and migration backups are retained for controlled rollback.

## 6. Frontend Architecture

The frontend is a Vue 3 single-page application. Assets are located in `public/assets`, routing is hash-based, and state uses
Vue's reactivity system. CSP rules require external scripts/styles or an explicit nonce for unavoidable inline content.

## 7. CI and Beads Tracking

The `Quality` and `Security audit` workflows ignore changes that are limited to `.beads/**`. This keeps Beads status
commits from consuming the full application, container and dependency test matrix. Manual runs, scheduled security audits,
reusable `workflow_call` invocations and commits containing any non-Beads change remain enabled.

When these workflows are configured as required pull-request checks in GitHub, path-filtered workflows can remain pending
when skipped. In that setup, either do not require these two checks for Beads-only pull requests or keep pull-request
validation enabled and apply the optimization only to direct Beads pushes on `main`.
