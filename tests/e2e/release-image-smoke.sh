#!/usr/bin/env bash
set -euo pipefail

export VERIFIED_IMAGE="${1:?Usage: release-image-smoke.sh <immutable-image-ref>}"
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-extplorer-release-smoke-$$}"
if [ -z "${EXTPLORER_HTTP_PORT:-}" ]; then
    EXTPLORER_HTTP_PORT="$(node -e 'const server=require("net").createServer(); server.listen(0,"127.0.0.1",()=>{console.log(server.address().port);server.close();});')"
fi
export EXTPLORER_HTTP_PORT
export E2E_BASE_URL="${E2E_BASE_URL:-http://127.0.0.1:$EXTPLORER_HTTP_PORT}"
export EXTPLORER_BASE_URL="$E2E_BASE_URL/"
export E2E_USERNAME=e2e-admin E2E_PASSWORD=e2e-admin-password
fixture_directory="$(mktemp -d)"
export EXTPLORER_ADMIN_PASSWORD_HOST_FILE="$fixture_directory/admin-password"
umask 077
printf '%s\n' "$E2E_PASSWORD" > "$EXTPLORER_ADMIN_PASSWORD_HOST_FILE"
node -e 'require("fs").writeFileSync(process.argv[1], JSON.stringify({services: Object.fromEntries(["extplorer-init", "extplorer-app"].map(name => [name, {image: process.env.VERIFIED_IMAGE, pull_policy: "never"}]))}))' "$fixture_directory/image.json"
compose=(docker compose -f docker-compose.yml -f docker-compose.secrets.yml.example -f tests/e2e/docker-compose.yml -f "$fixture_directory/image.json")
cleanup() {
    status=$?
    if [ "$status" -ne 0 ]; then "${compose[@]}" logs --no-color; fi
    "${compose[@]}" down --volumes --remove-orphans
    rm -r "$fixture_directory"
    exit "$status"
}
trap cleanup EXIT
"${compose[@]}" up --detach --wait --wait-timeout 180
app_container="$("${compose[@]}" ps -q extplorer-app)"
docker top "$app_container" -eo user,pid,args | awk 'NR > 1 {print $1}' | sort -u | grep -qx 82
npm run e2e
