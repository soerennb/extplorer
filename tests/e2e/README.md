# End-to-end smoke tests

The Playwright suite exercises the production Docker image in Chromium. It
covers health and access protection, local login/logout, and a file lifecycle
including upload, download, rename, trash, restore, and cleanup.

```bash
docker build --build-arg APP_VERSION=e2e -t extplorer3:e2e .
npm ci
npm run e2e:install
docker compose -f docker-compose.yml -f tests/e2e/docker-compose.yml up --detach --wait --wait-timeout 120
npm run e2e
docker compose -f docker-compose.yml -f tests/e2e/docker-compose.yml down --volumes --remove-orphans
```

If port 8080 is occupied, use the same alternate URL for Compose and
Playwright:

```bash
EXTPLORER_HTTP_PORT=18080 E2E_BASE_URL=http://127.0.0.1:18080 docker compose -f docker-compose.yml -f tests/e2e/docker-compose.yml up --detach --wait --wait-timeout 120
E2E_BASE_URL=http://127.0.0.1:18080 npm run e2e
```

Failure artifacts are written to `test-results/` and `playwright-report/`.

## Native release routing checks

Before publishing each native release, check the extracted final ZIP in an
isolated installation with separate writable state and a test administrator.
Use the ZIP's application and production dependencies, rather than loading
code or configuration from the checkout. These checks supplement the archive
translation gate and the root-only Docker smoke suite.

Run each URL layout below with a fresh browser context and no existing
step-up grant. `EXTPLORER_BASE_URL` includes the application directory and
trailing slash; `app.indexPage` selects the front-controller URL independently.
Configure the test web server to serve static assets and forward application
requests to the extracted `public/index.php`.
For explicit-index cases, route API requests through the actual
`index.php/api/...` URL (a `307` canonical redirect from the browser's
base-directory API URL is sufficient). Check the final response URL in the
network panel so these cases exercise the index prefix, rather than only
visiting an indexed page while API requests still use rewritten paths.

| Layout | Example base URL | `app.indexPage` | Settings endpoint |
| :--- | :--- | :--- | :--- |
| Domain root, rewritten | `https://files.example.com/` | empty | `/api/settings` |
| Subdirectory, rewritten | `https://files.example.com/extplorer/public/` | empty | `/extplorer/public/api/settings` |
| Domain root, explicit index | `https://files.example.com/` | `index.php` | `/index.php/api/settings` |
| Subdirectory, explicit index | `https://files.example.com/extplorer/public/` | `index.php` | `/extplorer/public/index.php/api/settings` |

The browser matrix can be run directly against the final ZIP:

```bash
node tests/e2e/native-routing.cjs builds/extplorer3-3.1.1.zip
```

It creates an isolated installation and writable state for each layout, removes
translation sources, and checks a large binary chunk upload plus the settings
step-up flow. API requests must use the configured index prefix. Results and the
ZIP checksum are written to `build/native-routing-results.json`. The Release
workflow runs this gate against the actual ZIP even when earlier CI checks are
reused.

For each layout:

1. Log in and open **Admin Settings → Mounts → External Mounts**.
2. Set the external-mount path allowlist to a dedicated temporary directory
   outside the extracted code and managed storage, then save.
3. Verify that `POST api/settings` returns `428` with
   `error=step_up_required` and a nonempty `X-CSRF-HASH` header.
4. Confirm the administrator's current password. Verify `POST
   api/security/step-up` returns `200` with `X-CSRF-HASH`, followed by a
   successful settings retry (`200` with `X-CSRF-HASH`).
5. Reload the page and confirm the allowlist persisted. Change it again and
   save within the grant window: expect `200` without another password prompt.
6. Check for unexpected failed requests, browser exceptions and CSP
   violations. Remove the isolated installation and stop its test server.

The automated route and cookie/token regeneration regressions run with
`composer test` in the normal Quality PHP matrix (PHP 8.2–8.5). To run them
alone, use `composer test -- --filter CsrfHeaderFilterTest`. They cover API
and public-share responses with statuses `200`, `428` and `400`, non-API
routes, and rejection of a stale token after regeneration. The Docker settings
browser test checks the complete `428 → 200 → 200` flow and refresh headers.

Record the tested archive checksum and all four layout results with the
release validation evidence. A missing refresh header or failed confirmation
must be fixed before publication; do not disable CSRF regeneration to make a
build pass.

## Cross-version release upgrade

Build the candidate ZIP and image with the release version. Download the
published baseline ZIP and run the isolated cross-version checks:

```bash
gh release download v3.1.1 --repo soerennb/extplorer --pattern eXtplorer-3.1.1-release.zip --dir build/upgrade-baseline
node tests/e2e/release-upgrade.cjs build/upgrade-baseline/eXtplorer-3.1.1-release.zip builds/extplorer3-3.1.2.zip extplorer3:security-mail
```

The baseline ZIP SHA-256 and multi-platform image digest are pinned in
`tests/release/baseline-3.1.1.json`. The fixture seeds state using v3.1.1's own
framework and services, then verifies passwords, permissions, encrypted 2FA
and mount secrets, a real encrypted SSH key, WebDAV credentials, binary files,
shares, an external local mount, trash, versions, resumable uploads and quarantine
across the upgrade. It exercises pending work, restarts the candidate, checks
code rollback against post-upgrade state, and restores the original data backup.
Fresh browser sessions verify login and strict CSP at each stage.

Without the last image argument only the native check runs; Release uses this
mode against its actual final ZIP. With the image argument, the test also pulls
the pinned published Docker baseline, uses isolated Compose volumes, checks
atomic code rollback and full data restore, and deletes its temporary stack.
Results and candidate checksums are recorded in `build/release-upgrade-results.json`.
No production installation or writable directory is used.

`bash tests/e2e/release-image-smoke.sh <image-ref>` checks a single built image
with isolated volumes, the chmod-600 secret overlay and Chromium smoke suite.
Release runs this on each architecture's actual image digest.

## Mail security boundary

The normal administration smoke test also uploads an extensionless script,
changes its mode to 700, and verifies that settings writes and mail probes
cannot select it as Sendmail. It reuses the existing authenticated test session
so the suite stays within the production login rate limit.

Run the separate operator-owned Sendmail fixture with:

```bash
EXTPLORER_TEST_IMAGE=extplorer3:security-mail sh tests/integration/mail/run.sh
```

It has no network, runs PHP without root, and installs the trusted test mailer
outside writable storage as root. It verifies real delivery through a filename
containing shell metacharacters, then refuses a read-only script owned by PHP
and refuses an unmigrated legacy path. No real email is sent.

## External authentication

`node tests/e2e/external-auth.cjs [extracted-archive-directory]` starts its own
TLS-enabled Keycloak 26.8.1 container (pinned digest), a separate website
fixture and native PHP installations. It checks OIDC and the shipped custom
adapter, provisioning, real provider reauthentication, CSRF/CSP behavior and
logout at root/subdirectory URLs with and without `index.php`. Configuration
and writable state are isolated. Only its own browser contexts, servers and
container are closed on completion or handled failure. Docker, OpenSSL, PHP
and the installed Playwright Chromium are required. The Quality workflow
runs this independently of the existing local-login Docker smoke.
