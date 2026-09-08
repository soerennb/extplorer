#!/bin/sh

set -eu

compose_args=${COMPOSE_ARGS:-}
echo '[extplorer-deploy] rendering Nginx configuration'
docker compose $compose_args exec -T extplorer-web \
  /bin/sh /usr/local/bin/extplorer-nginx-entrypoint --render-config
echo '[extplorer-deploy] validating Nginx configuration'
docker compose $compose_args exec -T extplorer-web nginx -t
echo '[extplorer-deploy] reloading Nginx configuration'
docker compose $compose_args exec -T extplorer-web nginx -s reload
echo '[extplorer-deploy] Nginx reload completed'
