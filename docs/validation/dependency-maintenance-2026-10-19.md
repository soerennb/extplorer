# Dependency maintenance validation — 2026-10-19

Application version: **3.1.0**. Validated implementation on `main`:
`3047326dce1ee01eea7ad9609021720cbc2d6439`. Its complete tracked tree matches
the final PR #68 revision `700e609f96ec0e22f7e80018a95e62bb5e7840af`, from which
the local image and native archives below were built. This evidence document
is a subsequent documentation-only change. No release or container was published.

Tracking: `ex3-ee0` (#69), `ex3-5a6` (#70), `ex3-81f` (#68), and final
verification `ex3-2hg`. Final-main workflow run links and results are retained
in `ex3-2hg`.

## Changes and pull-request gates

| PR | Change | Final Quality / Security / Secret scanning runs |
| --- | --- | --- |
| [#69](https://github.com/soerennb/extplorer/pull/69) | CodeIgniter 4.7.4 → 4.7.5; remove the now-unmatched `isSecure()` PHPStan suppression | [Quality](https://github.com/soerennb/extplorer/actions/runs/39256808763), [Security](https://github.com/soerennb/extplorer/actions/runs/39256808761), [Secrets](https://github.com/soerennb/extplorer/actions/runs/39256808756) |
| [#70](https://github.com/soerennb/extplorer/pull/70) | Vue runtime/compiler 3.5.43 → 3.5.44; Playwright 1.64.0 → 1.64.1; generated runtime and provenance inventory | [Quality](https://github.com/soerennb/extplorer/actions/runs/39257387931), [Security](https://github.com/soerennb/extplorer/actions/runs/39257387969), [Secrets](https://github.com/soerennb/extplorer/actions/runs/39257387944) |
| [#68](https://github.com/soerennb/extplorer/pull/68) | phpseclib 3.0.57 → 4.0.2, including SFTP namespace, signatures, exception semantics and atomic-write cleanup | [Quality](https://github.com/soerennb/extplorer/actions/runs/39257956858), [Security](https://github.com/soerennb/extplorer/actions/runs/39257956770), [Secrets](https://github.com/soerennb/extplorer/actions/runs/39257956802) |

All applicable checks passed before each squash merge. The final Quality run
covers PHP 8.2–8.5, PHPStan, MySQL/PostgreSQL/Redis integration, frontend/CSP,
Apache/Nginx configuration, real FTP/FTPS/SFTP servers, image scanning and
Docker/Chromium smoke tests. The separate scheduled/manual-only native-security
and container-security jobs are intentionally skipped on PR events; they are
included in the final manual Security run on `main`.

## Local checks and toolchain

PHP 8.5.11; Node 24.21.0; Composer 2.10.3; PHPUnit 11.5.57;
PHPStan 2.3.1; Playwright 1.64.1; Docker 29.1.3; Trivy 0.75.0.
The downloaded Trivy executable was verified against its publisher's SHA-256
checksums. Composer still targets PHP 8.2; runtime images remain on PHP 8.5 /
Alpine 3.24.

| Check | Result |
| --- | --- |
| PHP suite | 334 tests, 1,323 assertions; nine environment-dependent cases skipped locally and exercised by remote/Redis checks |
| SFTP adapter regressions | Ten cases, 57 assertions; missing-file versus access/transport errors, binary downloads, void mutation contracts, failed activation and interrupted-upload cleanup |
| Real FTP/FTPS/SFTP integration | Eight cases, 118 assertions; uploads, editing, copy/delete, quarantine, encrypted/unencrypted SFTP keys, wrong password/host pin, chmod and recursive/idempotent deletion |
| PHPStan and Composer validation | Passed; obsolete baseline entry removed without new suppressions |
| Frontend behavior tests | Eleven passed |
| JavaScript build | Passed; rebuilding leaves committed runtime/render assets unchanged |
| Copied browser assets | 43 files from seven package records verified |
| Vue provenance | Exact 3.5.44 npm tarball matches lockfile SHA-512; shipped runtime is byte-identical to the publisher artifact |
| Translation build/check | Four locales, 802 keys; passed |
| Composer, NPM and copied-browser-package audits | Zero reported advisories |
| CSP source scan | No unnonced inline scripts/styles, inline handlers or style attributes |
| New local image and Chromium suite | Six tests passed; chmod-600 secret bootstrap leaves PHP-FPM running as UID 82 |

The cached local remote-fixture image had an expired two-day FTPS certificate.
Rebuilding that disposable fixture refreshed the certificate; the final eight
remote integration cases passed with normal certificate verification enabled.

## Container scan

Local image: `extplorer3:maintenance-20261019` (also tested as `extplorer3:e2e`).
Image ID / local repository digest:
`sha256:023cceb00fdf914b6f3c43917e52e827003ab5096a6dee05001ca8e3c4810279`.
Build marker: `3.1.0-maintenance-700e609`.

Resolved build inputs:

- `php:8.5.11-fpm-alpine3.24@sha256:fa01fb1645cd0fc566a5f146b099adace33b906571f972f71f2182a7c12d1cd7`
- `composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac`

Trivy's Alpine database was updated at **2026-10-19 07:19 UTC** and downloaded
at **13:12 UTC**. The scan includes all vulnerability severities and unfixed
findings: **zero HIGH/CRITICAL**, one MEDIUM, no other findings.

Remaining finding: [CVE-2026-58055](https://avd.aquasec.com/nvd/cve-2026-58055),
`nghttp2-libs` **1.69.0-r0**, fixed in **1.70.0-r0**, reported by Alpine Secdb.
Follow-up **`ex3-r9j`** tracks the smallest compatible runtime/base-image fix
and its validation. The existing HIGH/CRITICAL image gate remains unchanged;
no advisory was suppressed.

## Final native archives

Built with `bash build.sh`. Both archive gates verified all four runtime
translation bundles and the packaged Login controller without translation
build sources. Production dependencies were packaged; development dependencies
were restored afterwards.

| Artifact | SHA-256 |
| --- | --- |
| `builds/extplorer3-3.1.0.zip` | `882ba067b4a19eb488d8b0d21c88caf7a2671fc4454fbb998a0c1bcc88e3582b` |
| `builds/extplorer3-3.1.0.tar.gz` | `43448c380e6379afcba70bacf140a57225ba30c3d3bbe61d77c83a4f356d52ec` |

The native browser matrix used the extracted final ZIP, isolated writable
state, and no translation sources. Every layout passed binary chunk upload,
the settings `428 → 200 → 200` step-up flow, CSRF refresh headers, browser-error
monitoring and CSP monitoring:

| Installation path | Index page | Result |
| --- | --- | --- |
| `/` | rewritten | Passed |
| `/extplorer/public/` | rewritten | Passed |
| `/` | `index.php` | Passed |
| `/extplorer/public/` | `index.php` | Passed |

Raw local logs, full container-scan JSON, archive checksums, captured PR checks
and native routing results are retained under
`build/dependency-maintenance-2026-10-19/` (ignored build artifacts).
