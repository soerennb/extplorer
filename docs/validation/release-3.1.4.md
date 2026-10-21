# Release v3.1.4 validation

Security maintenance release for the PHP releases of 2026-10-21. Both
Dockerfile stages move from `php:8.5.11-fpm-alpine3.24` to
`php:8.5.12-fpm-alpine3.24`; Alpine 3.24, Composer, PECL extensions, the
`nghttp2-libs>=1.70.0-r0` requirement and all application code are unchanged.

PHP 8.5.12 fixes GHSA-w8hw-w9fx-j79w and GHSA-6wmf-4rv5-p3rp (state corruption
via bailout) plus memory-safety defects in BZ2, DOM, FFI, GD and Intl. The
corresponding fixes for the supported native lines are 8.2.35, 8.3.36 and
8.4.27; native installations need the host interpreter update, not the archive.

## Verification

No local test run. On release commit `41c35be`, the
[Quality](https://github.com/soerennb/extplorer/actions/runs/39689445739),
[Security audit](https://github.com/soerennb/extplorer/actions/runs/39689445680)
and [Secret scanning](https://github.com/soerennb/extplorer/actions/runs/39689445821)
workflows passed, so the
[Release run](https://github.com/soerennb/extplorer/actions/runs/39690124643)
reused them. Its archive job (final ZIP routing layouts, upgrade/rollback from
3.1.1), both architecture image builds and publication succeeded.

## Published artifacts

[Version 3.1.4](https://github.com/soerennb/extplorer/releases/tag/v3.1.4) was
published as the latest stable release at 2026-10-21 23:02:31 UTC.

| Published artifact | Verified digest |
| --- | --- |
| `eXtplorer-3.1.4-release.zip` | SHA-256 `556ddb8505cfa99421d56b0047f6010e146f632ae84109fd31ad92566fcd4e90` |
| AMD64/ARM64 manifest | `sha256:774a6800625c740b90864fa4a63ea6fbf6f9301bced33f3562e9f972c0772634` |

Registry inspection confirmed `3.1.4` and `latest` resolve to that manifest and
the image reports `PHP_VERSION=8.5.12` and `EXTPLORER_IMAGE_VERSION=3.1.4`.

## Upgrade

Container deployments pull 3.1.4. Upgrades from 3.1.1 or earlier must follow
the [3.1.2 requirements](release-3.1.2.md): operator-configured Sendmail path,
saved and retested mail settings, bounded delegated administration and the
strict-mode FTPS refusal.
