# Changelog

All notable changes to eXtplorer 3 are documented here.

## [Unreleased]

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

[Unreleased]: https://github.com/soerennb/extplorer/compare/v3.0.0-beta.6...HEAD
[3.0.0-beta.6]: https://github.com/soerennb/extplorer/releases/tag/v3.0.0-beta.6
[3.0.0-beta.5]: https://github.com/soerennb/extplorer/releases/tag/v3.0.0-beta.5
