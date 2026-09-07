#!/bin/sh

set -eu

: "${EXTPLORER_PUBLIC_URL:?Set EXTPLORER_PUBLIC_URL to the deployed HTTPS URL}"
compose_args=${COMPOSE_ARGS:-}
code_root=${EXTPLORER_CODE_ROOT:-/var/www/html/current}

echo '[extplorer-deploy] checking Compose service state'
docker compose $compose_args ps --status running

echo '[extplorer-deploy] checking application readiness'
docker compose $compose_args exec -T extplorer-app php "${code_root}/spark" system:readiness

echo '[extplorer-deploy] checking Nginx configuration'
docker compose $compose_args exec -T extplorer-web nginx -t

echo '[extplorer-deploy] checking public health endpoint'
health_url=${EXTPLORER_PUBLIC_URL%/}/health
curl --fail --silent --show-error --location --max-time 15 "$health_url"
printf '\n'

if [ -n "${TRAEFIK_API_URL:-}" ]; then
    : "${TRAEFIK_ROUTER_NAME:?Set TRAEFIK_ROUTER_NAME when TRAEFIK_API_URL is configured}"
    router_url=${TRAEFIK_API_URL%/}/api/http/routers/${TRAEFIK_ROUTER_NAME}
    echo '[extplorer-deploy] checking Traefik router reconciliation'
    curl --fail --silent --show-error --max-time 15 "$router_url" >/dev/null
fi

echo '[extplorer-deploy] deployment verification passed'
