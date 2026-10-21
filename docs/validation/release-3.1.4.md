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

No local test run. The Quality, Security audit and Secret scanning workflows on
the release commit and the Release workflow gates (final ZIP routing layouts,
upgrade/rollback from 3.1.1 and both architecture image scans) provide the
evidence. Record the published ZIP checksum, multiarch digest and scan results
here after publication.

## Upgrade

Container deployments pull 3.1.4. Upgrades from 3.1.1 or earlier must follow
the [3.1.2 requirements](release-3.1.2.md): operator-configured Sendmail path,
saved and retested mail settings, bounded delegated administration and the
strict-mode FTPS refusal.
