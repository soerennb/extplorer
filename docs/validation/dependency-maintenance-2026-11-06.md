# Dependency maintenance — 2026-11-06

Tracking: `ex3-dq1`. Maintenance only; no release, tag or container publication.
The containing commit records the reviewed source and lockfiles.

## Updates

- Symfony HTTP client 7.4.21 → 7.4.23 fixes GHSA-gq53-v8w2-gjhj and GHSA-c7hm-wqwp-f9v8.
- CodeIgniter 4.7.6, phpseclib 4.0.3, PHPStan 2.3.5 and seven reviewed transitive updates; PHP platform remains 8.2.0.
- Vue runtime/compiler 3.5.45, Playwright 1.65.0 and Ace 1.45.2.
- Node 24.22.2 / npm 11.19.1; Composer builder and CI pins 2.11.1; Nginx 1.31.8-alpine3.24.
- PHP remains 8.5.12 / Alpine 3.24. Both PHP stages retain the existing security minimums.

## Validation

- PHP 8.2, 8.3, 8.4 and 8.5: 544 tests, 2,488 assertions, nine environment-dependent skips per runtime.
- Real FTP/FTPS/SFTP on container PHP 8.5.12: eight tests, 118 assertions passed.
- PHPStan 2.3.5 and Composer 2.11.1 validation passed. Local PHP CLI: 8.5.11; PHPUnit: 11.5.57.
- 25 frontend and 20 release-publication tests passed.
- Six Docker/Chromium smoke tests passed, including settings step-up, editor/diff and file operations.
- JavaScript build, 43 vendor file checks and translation build/check (four locales, 829 keys) passed.
- Vue and Ace publisher tarball SHA-512 values and shipped files verified against upstream.
- Composer locked audit, npm locked audit and isolated copied-vendor audit: zero advisories.
- Nginx syntax validation passed with the rendered upload-limit configuration.
- CSP view scan: no inline handlers or style attributes; inline script/style blocks retain nonces.
- ZIP and TAR.GZ packaged translation gates passed; all four native ZIP routing layouts passed with upload and CSRF/step-up checks.

## Container scans

Trivy 0.75.0, database updated 2026-11-06 18:59:52 UTC, downloaded 21:45:09 UTC.
Complete vulnerability scans include unfixed findings: zero findings for both images.

- Application: `extplorer3:maintenance-20261106`, image ID `sha256:b890272e27f4d4c36c21f902ad2b367e199efc932602f480c6e163f9f9afe179`.
- PHP base: `php:8.5.12-fpm-alpine3.24@sha256:64535609509e0fd5c8529359e6b599a68ce42089defd565f7881efdae7687cea`.
- Composer: `composer:2.11.1@sha256:8de2184c6de224f4689e8f80ed5f5edaf0030b6b8b7839a22a2bcb6722ac87d0`.
- Nginx: `nginx:1.31.8-alpine3.24@sha256:713fc2ebfd92e8217db451477182ee91944b123d2b1ee9fd5a8c6e91ca6c3a11`.

Raw scan JSON is retained locally under `build/dependency-maintenance-2026-11-06/`.

## Local archive checksums

These are unpublished maintenance test archives using the existing application version 3.1.4.
They do not replace previously published release artifacts.

- ZIP: `7fa7bda478e05d7733166718ed618b7920723c2b3313dd193ceb489a203e3e01`.
- TAR.GZ: `e9989a5619b08a1c46e039ce6355eba2e6e608e5550ba1ac6d763e15c0cda53a`.
