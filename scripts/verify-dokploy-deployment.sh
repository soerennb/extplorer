#!/bin/sh

set -eu

: "${EXTPLORER_PUBLIC_URL:?Set EXTPLORER_PUBLIC_URL to the deployed HTTPS URL}"
compose_args=${COMPOSE_ARGS:-}
code_root=${EXTPLORER_CODE_ROOT:-/var/www/html/current}
nginx_dump=$(mktemp)
login_headers=$(mktemp)
cleanup() {
    rm -f "$nginx_dump" "$login_headers"
}
trap cleanup EXIT HUP INT TERM

echo '[extplorer-deploy] checking Compose service state'
docker compose $compose_args ps --status running

echo '[extplorer-deploy] checking application readiness'
docker compose $compose_args exec -T extplorer-app php "${code_root}/spark" system:readiness

echo '[extplorer-deploy] checking Nginx configuration'
docker compose $compose_args exec -T extplorer-web nginx -t
docker compose $compose_args exec -T extplorer-web nginx -T >"$nginx_dump" 2>&1
if ! grep -Fq 'root /var/www/html/current/public;' "$nginx_dump" ||
    grep -Fq 'root /var/www/html/public;' "$nginx_dump"; then
    echo '[extplorer-deploy] Nginx document root is not /var/www/html/current/public' >&2
    exit 1
fi
if ! grep -Fq 'fastcgi_param HTTP_X_FORWARDED_PROTO $http_x_forwarded_proto;' "$nginx_dump"; then
    echo '[extplorer-deploy] Nginx does not forward X-Forwarded-Proto to PHP-FPM' >&2
    exit 1
fi

trusted_proxy_ips=$(docker compose $compose_args exec -T extplorer-app \
    printenv EXTPLORER_TRUSTED_PROXY_IPS 2>/dev/null | tr -d '\r' || true)
if [ -z "$(printf '%s' "$trusted_proxy_ips" | tr -d '[:space:]')" ]; then
    echo '[extplorer-deploy] EXTPLORER_TRUSTED_PROXY_IPS is empty; forwarded HTTPS cannot be trusted' >&2
    exit 1
fi
secure_redirect_setting=$(docker compose $compose_args exec -T extplorer-app \
    printenv app.forceGlobalSecureRequests 2>/dev/null | tr -d '\r' || true)
if [ "$secure_redirect_setting" != 'false' ]; then
    echo '[extplorer-deploy] app.forceGlobalSecureRequests must be false for the Dokploy deployment' >&2
    exit 1
fi

echo '[extplorer-deploy] checking public health endpoint'
health_url=${EXTPLORER_PUBLIC_URL%/}/health
curl --fail --silent --show-error --location --max-time 15 "$health_url"
printf '\n'

echo '[extplorer-deploy] checking PHP HTTPS detection through Traefik'
login_url=${EXTPLORER_PUBLIC_URL%/}/login
if ! curl --silent --show-error --max-time 15 --dump-header "$login_headers" \
    --output /dev/null "$login_url"; then
    echo '[extplorer-deploy] public login endpoint is unreachable' >&2
    exit 1
fi
login_status=$(awk 'NR == 1 { print $2; exit }' "$login_headers")
case "$login_status" in
    2*|3*) ;;
    *) echo "[extplorer-deploy] public login endpoint returned HTTP ${login_status:-unknown}" >&2; exit 1 ;;
esac
if [ "$login_status" = '307' ] || grep -Eiq '^location:[[:space:]]*http://' "$login_headers"; then
    echo '[extplorer-deploy] detected an HTTP redirect from the HTTPS deployment' >&2
    exit 1
fi
if ! grep -Eiq '^strict-transport-security:' "$login_headers"; then
    echo '[extplorer-deploy] PHP did not recognize the external request as HTTPS (missing HSTS)' >&2
    exit 1
fi

if [ -n "${TRAEFIK_API_URL:-}" ]; then
    : "${TRAEFIK_ROUTER_NAME:?Set TRAEFIK_ROUTER_NAME when TRAEFIK_API_URL is configured}"
    router_url=${TRAEFIK_API_URL%/}/api/http/routers/${TRAEFIK_ROUTER_NAME}
    echo '[extplorer-deploy] checking Traefik router reconciliation'
    curl --fail --silent --show-error --max-time 15 "$router_url" >/dev/null
fi

echo '[extplorer-deploy] deployment verification passed'
