# Dokploy Deployment Runbook

This repository contains a dedicated Dokploy Compose override. It keeps the ordinary Docker deployment self-contained and
connects only the Nginx `extplorer-web` service to Dokploy's external Traefik network.

## Deploy

1. Create or select a Dokploy Compose deployment from this repository.
2. Add `docker-compose.dokploy.yml` as the deployment override.
3. Set `DOKPLOY_NETWORK_NAME` if the installation uses a network other than `dokploy-network`.
4. Configure a domain for the `extplorer-web` service in Dokploy's Domains UI. The domain must target container port `80`.
5. Set `EXTPLORER_BASE_URL` to the public HTTPS URL, including the trailing slash.
6. Use `docker-compose.secrets.yml.example` as a template for a Dokploy secret file. Keep the administrator password and
   encryption key outside ordinary environment variables where Dokploy supports secret mounts. The overlay handles root-owned
   mode-600 secret files during initialization and drops the long-running PHP-FPM process to `www-data`.
7. Deploy and wait for the `extplorer-init`, `extplorer-app` and `extplorer-web` health states before considering the release successful.

The override intentionally removes host port publishing. Traefik reaches `extplorer-web` over the external network, while
`extplorer-app` and `extplorer-init` continue to use the private `extplorer-net` network.

## Verify routing after deployment

```bash
COMPOSE_ARGS='-f docker-compose.yml -f docker-compose.dokploy.yml' \
  EXTPLORER_PUBLIC_URL=https://files.example.com \
  ./scripts/verify-dokploy-deployment.sh
```

The verifier checks service state, the application readiness marker and
migration state, `nginx -t`, and the public `/health` route. If the Traefik
API is reachable, set `TRAEFIK_API_URL` and `TRAEFIK_ROUTER_NAME` as well; a
missing router then fails verification instead of being mistaken for a
successful Compose deployment. The script is deliberately usable from a
normal Docker host as well as from a Dokploy post-deploy hook.

The health endpoint is a static Nginx response. It must return HTTP 200 without depending on the public URL from inside the
container. A 404 from the public domain while `extplorer-web` is healthy indicates that Traefik has not reconciled its provider state.
Check that `extplorer-web` is attached to the selected external network, then trigger a Dokploy redeploy/provider reconciliation and
verify the router before declaring success. Restarting Traefik is a last-resort platform operation, not an application
initialization step.

## File mounts and Nginx reloads

If Dokploy replaces `docker/nginx/default.conf` or another mounted file, the running Nginx process does not automatically
reload merely because the bind mount changed. The safe platform sequence is:

```bash
COMPOSE_ARGS='-f docker-compose.yml -f docker-compose.dokploy.yml' \
  ./scripts/reload-nginx-config.sh
```

The supplied Nginx entrypoint already runs `nginx -t` before startup. A platform-side file-mount implementation should write
to a temporary file, validate it, atomically replace the target, and reload Nginx. If validation fails, retain the previous
configuration and fail the deployment. The platform API and documentation should use one identifier consistently (`serviceId`
or `composeId`) for file mounts.

## Upgrade and rollback

The image is pulled on every Compose deployment, but the code volume is synchronized by `extplorer-init` using the image version and
content hash. Inspect the init log for the active release id. Previous releases remain in the code volume so a compatible
rollback can be activated with:

```bash
docker compose -f docker-compose.yml -f docker-compose.dokploy.yml run --rm extplorer-init --rollback RELEASE_ID
docker compose -f docker-compose.yml -f docker-compose.dokploy.yml restart extplorer-app extplorer-web
```

Do not downgrade across an incompatible data schema. Migration backups are stored in the persistent writable volume under
`backups/migration-YYYYmmdd-HHMMSS/`.

## Network exhaustion

If Docker reports `all predefined address pools have been fully subnetted`, remove unused Docker networks or configure the
host's address pools. Do not add arbitrary networks to the application as a workaround. The Dokploy override only requires the
pre-existing external ingress network plus the application's private bridge network.

Dokploy's domain behavior and network attachment model are documented in the [official Domains documentation](https://docs.dokploy.com/docs/core/docker-compose/domains).
