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
