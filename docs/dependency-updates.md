# Dependency updates and release checks

Use this procedure for dependency maintenance and every deployable archive or
container build. Security fixes take priority; routine maintenance stays within
the existing major versions and runtime release lines. Plan major migrations in
separate issues, including compatibility and rollout requirements. If an advisory
can only be fixed by a major migration, record it as a release blocker rather
than ignoring the advisory or weakening the audit.

## Version sources and update policy

| Component | Version source | Maintenance rule |
| --- | --- | --- |
| PHP application dependencies and development tools | `composer.json`, `composer.lock` | Preserve PHP 8.2 compatibility and review targeted patch/minor updates. |
| Vue runtime/compiler and Playwright | `package.json`, `package-lock.json` | Keep the Vue runtime and compiler aligned; install from the lockfile. |
| Node build toolchain | `.nvmrc`, `.node-version`, README | Keep both pins identical within the selected Node 24 LTS line. |
| PHP container and Composer builder | Dockerfile | Update both PHP stages together within PHP 8.5 and Alpine 3.24; align Composer with CI. |
| Nginx | Compose and the Quality syntax-check image | Keep both images aligned within the existing 1.31/Alpine 3.24 line. |
| PECL extensions | Dockerfile | Review Redis and SSH2 advisories and compatible updates separately. |
| GitHub Actions | Workflow and composite-action SHA pins | Keep immutable SHA pins and update their version comments together. |

Dependabot already checks Composer, NPM, Docker and GitHub Actions weekly and
groups patch/minor version updates. Preserve that schedule and grouping. Review
major PRs separately; grouping does not prohibit major PRs and is not an
automatic merge policy. Node version files, copied browser files and Composer
tool-version strings also need manual review.

Confirm stable releases and exact container tags against the publisher before
updating a pin. For an advisory, choose the smallest compatible fixed release.
Do not raise the PHP platform requirement, switch runtime branches, or adopt
prereleases as part of routine maintenance.

For native deployments, also review and record the host PHP patch level within
the supported 8.2–8.5 lines. Updating an archive does not update the host's PHP
interpreter.

Use the package manager to resolve lockfile changes, then review the complete
diff. For a targeted Composer patch, for example:

```bash
composer outdated --locked --direct
composer update vendor/package --patch-only --minimal-changes --with-dependencies
composer validate --strict
npm outdated
```

Replace `vendor/package` with the reviewed package. Omit `--patch-only` only for
an explicitly reviewed minor update. Keep unrelated packages locked. For NPM,
update only the reviewed package within its current major version and commit
the resulting lockfile. Do not use `npm audit fix --force`.

## Copied browser asset inventory

Inventory checked on 2026-10-05. These files are shipped to browsers, but only
Vue's active runtime is currently generated from an NPM-locked dependency.
Composer and NPM audits alone do not cover all shipped browser libraries or
dependencies embedded in their bundles.

| Library | Bundled version | Files and update source |
| --- | --- | --- |
| Vue, active runtime | 3.5.43 | `public/assets/js/vue.runtime.global.prod.js`; `npm run build:js` copies it from the locked `vue` package and uses the locked compiler. |
| Vue, legacy full builds | Removed | The unused 3.5.41 full builds are no longer shipped; only the locked 3.5.43 runtime used by the application remains. The audit advisory applied to the package's server-renderer dependency, which was not present in those browser files. |
| Bootstrap | 5.3.8 | `bootstrap.bundle.min.js` and `bootstrap.min.css`; obtain matching JS/CSS from the [Bootstrap package](https://www.npmjs.com/package/bootstrap). |
| SweetAlert2 | 11.26.25 | `sweetalert2.min.js` and `sweetalert2.all.min.js`; use matching files from the [publisher release](https://github.com/sweetalert2/sweetalert2/releases). |
| SweetAlert2, copied stylesheet | 11.26.25 | `sweetalert2.min.css` is byte-for-byte identical to the CSS shipped by `sweetalert2@11.26.25`. |
| Remix Icon | 4.9.1 | `remixicon.css` and `remixicon.woff2`; icon rules match the upstream package, with the font-face narrowed to the shipped WOFF2 font and local path. |
| Ace | 1.44.0 | `public/assets/vendor/ace/`; `scripts/ensure-ace-assets.sh` verifies the pinned `ace-builds` tarball's SHA-512 SRI before copying modes, workers and themes. |
| jsdiff (`diff` npm package) | 9.0.0 | `public/assets/js/diff.min.js`; byte-for-byte identical to the publisher's package bundle. |
| diff2html | 3.4.56 | `diff2html-ui.min.js` and `diff2html.min.css`; byte-for-byte identical to the publisher's npm bundle. The UI bundle includes `diff@^8.0.3`, `@profoundlogic/hogan@^3.0.4` and optional `highlight.js@11.11.1`. |

The machine-readable [vendor asset manifest](../scripts/vendor-assets.json)
records the exact npm package version, registry tarball URL, publisher SRI and
SHA-256 of all 43 copied browser vendor files. Package downloads were verified
with `npm pack`, and shipped files were compared with their publisher artifact.
Remix Icon's font is byte-for-byte upstream; its CSS only changes the font-face
to use that WOFF2 file at the local path, while the icon rules match after
whitespace normalization. The manifest checker runs in the frontend quality
job and before archive packaging:

```bash
npm run check:vendor-assets
```

To update a copied asset, download the exact reviewed package with `npm pack`,
compare the shipped files with the publisher artifact, retain the package
license notice, then update both the SRI source record and shipped-file hashes
in the manifest. Do not infer an unknown bundle's version from the publisher's
current release. `scripts/ensure-ace-assets.sh` checks the `ace-builds@1.44.0`
tarball's pinned SHA-512 SRI before extracting or copying files. An
`ACE_VERSION` override also requires an explicit `ACE_TARBALL_INTEGRITY`;
archive packaging then checks the generated files against the manifest. A
download with a missing or mismatched integrity value fails closed.

Keep JS/CSS/fonts and Ace workers consistent. Exercise the affected editor,
dialogs, icons and file-history diff view, and check browser exceptions and CSP
violations after an asset update. Amend this inventory in the same change.

## Build and security gates

Install with the pinned Node toolchain and resolved lockfiles before testing:

```bash
nvm use
composer install --no-interaction --prefer-dist
npm ci
composer validate --strict
composer test
composer static:phpstan
npm run build:js
npm run check:vendor-assets
npm run audit:vendor-assets
composer i18n:build
composer i18n:check
composer audit --locked --no-interaction
npm audit --package-lock-only --audit-level=high
```

Quality must pass the PHP 8.2–8.5 matrix, frontend checks, Nginx syntax validation,
Docker runtime/storage checks and Playwright smoke suite, including login,
file operations and the settings step-up flow. Keep strict CSP and scan
`app/Views` for inline handlers, styles and scripts before finishing.

For a local PHP matrix, run suites sequentially or give each process a separate
`EXTPLORER_WRITE_PATH`. The tests temporarily change state files; concurrent
suites using the same writable directory interfere with each other. CI matrix
jobs use separate checkouts.

The existing container scans fail on fixable HIGH/CRITICAL vulnerabilities.
Run them against the newly built image, not a previous tag. Keep the Composer,
NPM and container audit thresholds and release gates intact. Record unfixed
container findings separately even though the existing scan ignores them for
its exit status.

A network error, unavailable registry or GitHub job that never acquires a runner
is an incomplete check. Restore access or rerun the failed jobs; do not record
them as successful audits. Local tests with an older installed PHPStan/PHPUnit
or Node version do not validate the updated toolchain. Record the actual
installed versions and obtain successful CI checks for the final commit.

Build native archives with `./build.sh`; it checks each final ZIP and TAR.GZ with
`scripts/check-release-i18n.php`. Preserve that archive gate, including when
same-commit CI results are reused. Follow the [translation runtime contract](translations.md#runtime-and-release-contract)
and run the [native release routing checks](../tests/e2e/README.md#native-release-routing-checks)
against the extracted final ZIP in all four URL layouts. Record archive checksums
and the CSRF refresh/step-up results. Root-only Docker smoke tests do not replace
this matrix.

## Release evidence

Attach the following evidence to the release validation issue or PR and link it
from the release notes. Do not publish until the required gates pass for the
final commit:

- Commit SHA, application version, lockfile changes, actual PHP/Node/Composer/
  PHPStan/PHPUnit versions and exact container tags or resolved image digests.
- Composer/NPM audit and container-scan results, timestamps, CI run links and
  remaining advisory IDs with affected packages and disposition.
- Copied browser asset review, source/integrity information for changed assets
  and explicitly unresolved provenance.
- PHP compatibility matrix, static analysis, frontend/translation build,
  Docker/Playwright results and browser/CSP checks.
- Final ZIP/TAR.GZ SHA-256 values, packaged translation checks and each of the
  four native routing results against the final ZIP.

The release workflow already requires successful Quality, Security and Secret
scanning checks for the exact commit on `main`, or executes reusable gates when
those checks are missing. Preserve that behavior. File follow-up Beads issues
for remaining work; an unresolved required check or security blocker prevents
publication.
