# Changelog

All notable changes to eXtplorer 3 are documented here.

## [Unreleased]

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

[Unreleased]: https://github.com/soerennb/extplorer/compare/v3.1.0...HEAD
[3.1.0]: https://github.com/soerennb/extplorer/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/soerennb/extplorer/releases/tag/v3.0.0
[3.0.0-beta.6]: https://github.com/soerennb/extplorer/releases/tag/v3.0.0-beta.6
[3.0.0-beta.5]: https://github.com/soerennb/extplorer/releases/tag/v3.0.0-beta.5
