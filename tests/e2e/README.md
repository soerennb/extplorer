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
