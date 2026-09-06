#!/bin/sh

set -eu

limit=${EXTPLORER_UPLOAD_MAX_FILE_MB:-100}
case "$limit" in
    ''|*[!0-9]*|0) echo 'EXTPLORER_UPLOAD_MAX_FILE_MB must be a positive integer' >&2; exit 1 ;;
esac
if [ "$limit" -gt 10240 ]; then
    echo 'EXTPLORER_UPLOAD_MAX_FILE_MB must not exceed 10240 MB' >&2
    exit 1
fi

sed "s/__EXTPLORER_UPLOAD_LIMIT__/${limit}m/g" \
    /etc/nginx/conf.d/default.conf.template \
    > /etc/nginx/conf.d/default.conf

nginx -t
exec nginx -g 'daemon off;'
