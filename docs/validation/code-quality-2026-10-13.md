# Code-quality remediation validation — 2026-10-13

Application version: **3.1.0**. Implementation commits: `2068102` and
`13889a7`, `3653c53`, `d74b683` and `23ce89c`, based on `8400343`. Beads epic `ex3-l0v` and routing bug `ex3-w32`
are complete. Genuine remaining PHPStan type contracts are tracked in `ex3-1ip`.

The implementation coordinates metadata lifecycle and authorization snapshots,
separates transfer staging from publication, and shares typed upload activation
across local, FTP, FTPS and SFTP storage. Browser requests share route generation,
CSRF regeneration and mutation ordering. Profile, upload, preview and drag/drop
responsibilities have dedicated modules; the duplicate administration mode is removed.
Destination-dependent actions stay disabled during directory navigation and capture
their target before asynchronous work. The compiled quick-administration overview
has a render regression test.

## Environment and local gates

PHP 8.5.11; Node 24.21.0; Composer 2.10.3; PHPUnit 11.5.57;
PHPStan 2.3.1; Playwright 1.64.0; Trivy 0.75.0.
The SFTP implementation uses phpseclib 3.0.57; the lockfile additionally gains
random_compat 9.99.100. Existing locked packages were not updated.

| Check | Result |
| --- | --- |
| Complete PHP suite | 321 tests, 1,266 assertions; six environment-dependent integration cases skipped locally and exercised separately below |
| Frontend behavior contracts | Eleven tests passed |
| PHPStan | Passed; no new suppressions; baseline reduced from 271 patterns / 678 diagnostics to 66 / 87 |
| Composer validation and locked package audit | Passed; zero advisories |
| NPM audit | Zero vulnerabilities |
| Copied browser assets | 43 assets from seven package records verified; no asset package update |
| JavaScript compilation | Passed |
| Translation build/check | Four locales, 802 keys; passed |
| CSP source scan | No unnonced inline scripts/styles, inline handlers or style attributes |
| Workflow and remote fixture shell validation | actionlint and ShellCheck passed |
| MySQL 8.4.11 lifecycle/transactions/concurrency | 13 tests, 136 assertions; passed |
| PostgreSQL 17 lifecycle/transactions/concurrency | 13 tests, 136 assertions; passed |
| Redis session sharing | One test, four assertions; passed |
| Real FTP/FTPS/SFTP fixtures | Five cases, 96 assertions; passed |
| Production Docker Chromium suite | Six tests; passed with browser error and CSP monitoring |
| Built-image vulnerability scan | Zero HIGH/CRITICAL findings, including unfixed findings |

The remote cases cover uploads, binary chunks, editing, copy/delete, quarantine
promotion, encrypted credential lease cleanup, changed mount namespaces, revoked
endpoints, encrypted SFTP key authentication and rejection of a mismatching host pin.
File, SQLite, MySQL and PostgreSQL concurrency tests check coherent multi-document
snapshots while two processes update the documents.

Built runtime image: `extplorer3:audit`, image ID
`sha256:ebb7df27664e035199bb607c8ddfa5013b92a625a07afc0c1873f40f2549d77c`.
Trivy image digest:
`sha256:af6acf9a6b85dfe389a1941505c0ce9efef52a4719635e1a962f022a3d855daa`.
The scan checks Alpine 3.24.2 and installed Composer packages. Scan JSON and
native routing results are retained locally under `build/`.

## Native archives

Both final artifacts pass `scripts/check-release-i18n.php`, which checks the
packaged Login controller without translation sources.

| Artifact | SHA-256 |
| --- | --- |
| `builds/extplorer3-3.1.0.zip` | `daa270c86e8af6832fb53b7caa9ec0092b570711bf2daf87afe52ed4d706a0ed` |
| `builds/extplorer3-3.1.0.tar.gz` | `2f22d8ba27a0ffb13f884647d341486f9223b50a13a6e02800949e037c36ce23` |

The browser matrix runs the extracted final ZIP with isolated writable state,
production dependencies and no translation sources. Each layout passes a large
binary chunk upload and the settings step-up sequence `428 → 200 → 200`, with
CSRF refresh headers and browser/CSP checks:

| Installation path | Index page | Result |
| --- | --- | --- |
| `/` | rewritten | Passed |
| `/extplorer/public/` | rewritten | Passed |
| `/` | `index.php` | Passed |
| `/extplorer/public/` | `index.php` | Passed |

These are local implementation checks, not a published release. The automated
PHP 8.2–8.5 and security/secret-scanning workflows must pass for the exact release
commit before publication. The release workflow retains the packaged translation
gate and now always runs all four native URL layouts, including when earlier
Quality checks are reused.

The code commit `23ce89c` passes [Quality](https://github.com/soerennb/extplorer/actions/runs/38357684921),
[Security audit](https://github.com/soerennb/extplorer/actions/runs/38357684875)
and [Secret scanning](https://github.com/soerennb/extplorer/actions/runs/38357684863).
This includes the PHP 8.2–8.5 matrix, storage integrations, real remote fixtures,
frontend/CSP checks and production Docker browser suite.
Subsequent evidence-only changes do not alter the application or its archives.
