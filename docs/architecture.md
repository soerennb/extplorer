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

`Quality` and `Security audit` use positive application, dependency, Docker and workflow path filters. Changes limited to
`.beads/**` therefore start no executable CI jobs. Documentation-only changes run the separate `Secret scanning` workflow,
while application and infrastructure changes run the complete relevant matrix. `bd dolt push` updates the Beads remote and
does not create a GitHub push event.

Quality owns the complete functional, compatibility, frontend, Docker E2E and container vulnerability gates. Security owns
dependency audits and runtime-policy checks on relevant changes; its focused security regression suite and independent
container scan remain available through the weekly schedule and manual dispatch. Secret scanning is independent so it also
covers documentation changes without starting the full test matrix.

Release tags first verify successful Quality, Security and Secret scanning runs for the exact SHA on `main`. If those checks
exist, the release reuses them; otherwise it executes the full reusable gates before publishing. Archive and container
publishing are blocked unless either path succeeds.

The repository ruleset currently does not require individual path-filtered checks. If required checks are introduced later,
use a stable aggregate gate rather than making skipped path-filtered workflows required, because GitHub can leave skipped
checks pending.
