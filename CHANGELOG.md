# Changelog

All notable changes to eXtplorer 3 are documented here.

## [Unreleased]

### Security

- Isolate direct FTP/SFTP logins from local accounts with the same name. Remote sessions can only use remote file operations; profiles, two-factor enrollment, re-authentication, shares, transfers, trash, versions and mounts of the local account are no longer reachable. Audit entries record remote identities as `remote:<protocol>://<user>@<host>`.
- Bind saved mount credentials to their endpoint. Changing the protocol, host, port, remote user, SFTP host key or FTPS pin requires entering the credentials again, for updates and connection tests alike.
- Limit delegated administrators to mounts, shares and transfers of accounts within their own authority, using the same rules as delegated user administration.
- Apply per-account allowed/blocked extensions to saving, renaming, copying, archiving, extracting and version restore, not only to uploads and WebDAV.
- Never let approved public share uploads replace existing files when upload scanning is enabled, and discard scanned uploads whose share was revoked, expired or narrowed in the meantime.
- Stop anonymous login failures from locking accounts indefinitely or cutting off their owners. An expired lock starts a new failure window. The password lock no longer ends existing sessions, remembered logins, WebDAV app passwords or public shares, and browsers that completed a sign-in for the account are exempt from the account-wide lock with their own failure limit (device cookie `extplorer_device`).
- Require re-authentication before enrolling two-factor authentication, so a hijacked session cannot lock the owner out.
- Render alert titles and messages as text. Server errors, filenames and remote responses were previously interpreted as HTML by the dialog library. Add `base-uri 'none'` and `frame-ancestors 'self'` to the content security policy and source rules against interpolated dialog markup and `innerHTML`.
- Bind re-authentication grants to the token returned by step-up, which the page keeps only in memory. A copied session cookie alone can no longer perform protected administration.
- Enforce the `rename` permission for renaming and moving files, as WebDAV already did.
- Exempt only the installer routes from authentication. Wildcard exceptions let any route with an `install` segment skip the authentication filter, and installations below a directory named `install` redirected every page.
- Notify share owners at most once per share and hour about anonymous downloads, and limit transfer mail to 100 recipients per account and hour.
- Compare a dummy password hash for unknown, disabled and locked accounts so response times do not reveal them.
- Validate login return paths correctly: paths containing the letter `r` were discarded, and carriage returns, tabs and other control characters are now rejected.
- Turn upload-mode shares into blind drop boxes that never list existing content, and stop serving hidden files and folders of shared folders through direct links or folder archives.
- Refuse SMTP hosts that resolve to link-local or cloud metadata addresses, and loopback or private addresses unless the operator sets `EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS=1`. Every resolved address is checked before each connection.
- Check every file of a copied folder, and of a folder moved into another storage, against the account's allowed/blocked extensions.
- Never allow local mounts of `/proc`, `/sys`, `/dev`, `/run/secrets` or the configured secret files, even when the mount root allowlist contains them.
- Require share passwords of at least 8 characters and treat malformed share password submissions as ordinary failures.
- Reject bidirectional, invisible and C1 control characters in new filenames, which could make a name display differently from its real type.
- Validate field types when administrators update accounts. Stored extension lists no longer break sign-in, and the string `false` no longer disables an account.
- Reject account passwords longer than 72 bytes, which bcrypt would silently truncate.

### Upgrade

- Accounts with an extension allowlist can no longer create, rename or extract files of other types through the web interface. Archives must use an allowed extension.
- Direct remote logins no longer show local shares, trash or mounts; those features report that they are unavailable for remote connections.
- `locked_until` is now only a password-guessing lock. Use `disabled` to suspend an account; it still ends sessions and public shares.
- The implicit `Public` mount for `<write path>/public` now requires `EXTPLORER_PUBLIC_MOUNT=1`. Set it to keep that common read-write mount, or move its content to `Shared`.
- Roles need `rename` to rename or move files. The built-in `user` role already includes it; review custom roles.
- Pages ask for re-authentication again after a reload before protected changes.
- A local or internal SMTP relay (for example `localhost:25`) requires `EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS=1`; otherwise mail delivery reports that the server address is not allowed.

## [3.1.4] - 2026-10-22

### Security

- Build the container image on PHP 8.5.12, which fixes GHSA-w8hw-w9fx-j79w and GHSA-6wmf-4rv5-p3rp (state corruption via bailout) and further memory-safety defects in PHP 8.5.11. Application code is unchanged.

### Upgrade

- Container deployments: pull 3.1.4. Native installations do not need the archive for this fix; update the host PHP to 8.2.35, 8.3.36, 8.4.27 or 8.5.12 instead.
- Upgrading from 3.1.1 or earlier: the [3.1.2 upgrade requirements](https://github.com/soerennb/extplorer/releases/tag/v3.1.2) apply.

## [3.1.3] - 2026-10-20

### Fixed

- Preserve IPv6 SMTP hosts through the security validation added in 3.1.2: bare and bracketed literals share one canonical identity, and the transport adds the brackets required by PHP sockets. A real IPv6 loopback SMTP fixture verifies message delivery.

### Security and upgrade

- Includes all [3.1.2 security corrections and upgrade requirements](https://github.com/soerennb/extplorer/releases/tag/v3.1.2). Install 3.1.3 when upgrading from 3.1.1 or earlier. Custom Sendmail paths require operator configuration, saved mail settings must be retested, delegated administration is bounded, and strict-mode legacy FTPS remains disabled.

## [3.1.2] - 2026-10-20

### Security

- Fix the application-controlled Sendmail executable reported by dutchypoo in GHSA-x294-8g9c-m6fc. Sendmail now uses only an operator-configured executable outside application-managed storage, with trusted filesystem ownership and permissions and shell-safe quoting.
- Require explicit settings permission and recent reauthentication for mail probes. Probes use saved settings only, reject overrides, share an account rate limit and return safe error codes without SMTP diagnostics.
- Limit delegated user, role and group administration to the administrator's current permissions, home scope and extension restrictions, including indirect changes and stronger accounts.
- Refuse legacy FTPS in strict mode because PHP's FTP extension cannot authenticate the actual connection. Confine FTP passive data connections to the authorized control peer.
- Add security regression tests, isolated transport fixtures and pinned source scanning.

### Upgrade requirements

- Back up configuration, writable data and the encryption key before upgrading. For a custom Sendmail binary, set `EXTPLORER_SENDMAIL_PATH` in the server environment; the old web setting is never executed. The executable and every path component must be protected from modification by the PHP worker. Otherwise select SMTP or PHP mail.
- Save and retest mail settings after upgrading; old delivery confirmations are invalidated. Mail validation and test delivery require recent reauthentication and saved settings.
- Delegated administrators need `admin_settings` to access settings. `admin_users` alone no longer grants it; account, role and group changes cannot exceed delegated authority.
- Use SFTP instead of strict-mode legacy FTPS. Only an operator's explicit compatibility policy enables the legacy FTPS transport, with its documented certificate-verification limitation.

## [3.1.1] - 2026-10-19

### Security

- Require the fixed Alpine nghttp2 library in both PHP image stages to resolve CVE-2026-58055.
- Strengthen authorization snapshots and atomic state handling across file operations and metadata updates.

### Fixed

- Preserve the requested navigation destination during mutation refreshes and keep file-action targets stable while navigating.
- Unify local and remote upload activation, quarantine handling and metadata lifecycle operations.
- Restore quick-administration rendering and reliable remote integration fixtures in clean checkouts.

### Changed

- Update CodeIgniter to 4.7.5, Vue to 3.5.44 and the bundled SFTP implementation to phpseclib 4.0.2. SFTP no longer requires the optional SSH2 extension.
- Update Playwright and PHPStan and refresh reviewed dependency and deployment validation evidence.
- Gate release publication and all moving container aliases on the final archive and verified AMD64/ARM64 images.

## [3.1.0] - 2026-10-06

### Added

- Added revocable, one-time-display WebDAV app passwords in the user security profile.

### Changed

- Production URL discovery for native Apache/Nginx PHP-FPM installations can use the canonical server name when `EXTPLORER_BASE_URL` is unset; Docker Compose still requires the explicit URL. A persistent cache backend is still required in production.
- WebDAV uploads now enforce account extension rules, streaming size limits and per-user quota.
- Removed unused legacy Vue full-build assets and added verified provenance and integrity checks for copied browser vendor files and Ace build downloads.

### Fixed

- Fixed external-mount allowlist saves and other API/share token refreshes in subdirectory and `index.php` installations by matching relative routing paths; added permanent CSRF regression tests and native release checks (#60).
- Fixed login labels displaying translation keys in native ZIP installations by loading the bundled runtime translations; local and GitHub release archives now verify all login locales without translation sources (#59).
- Derived native production URLs from canonical `SERVER_NAME`, direct HTTPS state or a configured trusted proxy, and the front-controller directory; invalid names and non-HTTPS public URLs still fail closed.

### Security

- Authorization changes immediately invalidate affected sessions and persistent login grants.
- Public shares now revalidate owner permissions and reject ambiguous legacy or external mount paths.
- Accounts protected by 2FA can no longer use the normal account password to bypass 2FA over WebDAV.

## [3.0.0] - 2026-09-19

### Added

- Added aggregate, owner-scoped limits and expiry handling for resumable upload and transfer staging data.
- Added fail-closed storage boundary and mount validation for writable roots, local mounts, symlinks and managed application paths.
- Added centralized filename policy enforcement across uploads, editor saves, file operations, archive extraction and WebDAV.

### Changed

- Hardened share and transfer flows with explicit public, owner and transfer payloads, strict path resolution and public-share CSRF protection.
- Hardened local, FTP, SFTP and WebDAV file operations against path traversal, symlink escapes and unsafe archive entries.
- Improved remote endpoint allowlist validation and localized invalid-settings responses.
- Switched application asset cache-busting to the application version for predictable deployments.

### Security

- Added protections for executable and server-configuration filenames, storage/webroot overlap, unsafe mounts and staging-resource exhaustion.
- Added ownership, locking, quota and cleanup checks for resumable uploads and internal transfers.
- Added regression coverage for share-data redaction, public uploads, login redirects, remote throttling, archive extraction and filesystem boundaries.

## [3.0.0-beta.6] - 2026-09-08

### Added

- Added an authenticated transfer capability endpoint that reports whether file sending is available.
- Added email delivery verification before file transfers can be started.
- Added resilient locale manifest handling with a built-in fallback so the language selector remains populated when the manifest is missing or invalid.
- Added automatic migration and backup handling for legacy initial administrator password state.

### Changed

- Fresh browser setup now keeps the username and password selected for the first administrator instead of forcing an unnecessary password change after login.
- Existing installations release the password-change flag only for the unambiguous first administrator with a custom password; the historical `admin` password remains protected by the forced-change state.
- A successful email delivery test remains valid across requests and restarts without an automatic time-based expiry. Effective mail configuration changes and actual notification failures invalidate the verification.
- File transfer notifications are rolled back when delivery fails, preventing a transfer from being reported as sent when its email notification was not delivered.
- Dokploy deployments now preserve trusted HTTPS proxy information without trusting forwarded headers from arbitrary clients.

### Fixed

- Password changes now return a stable, localized error when the current password is incorrect instead of exposing an unexpected server error.
- Docker/Nginx startup repairs stale legacy document-root templates and verifies the active versioned release path.

## [3.0.0-beta.5] - 2026-09-07

### Added

- Added Docker secret overlay support for root-owned secret files while keeping the long-running PHP-FPM process unprivileged.

### Fixed

- Improved Docker runtime and secret handling checks during release quality validation.

[Unreleased]: https://github.com/soerennb/extplorer/compare/v3.1.1...HEAD
[3.1.1]: https://github.com/soerennb/extplorer/compare/v3.1.0...v3.1.1
[3.1.0]: https://github.com/soerennb/extplorer/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/soerennb/extplorer/releases/tag/v3.0.0
[3.0.0-beta.6]: https://github.com/soerennb/extplorer/releases/tag/v3.0.0-beta.6
[3.0.0-beta.5]: https://github.com/soerennb/extplorer/releases/tag/v3.0.0-beta.5
