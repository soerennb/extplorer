#!/bin/sh

set -eu

template=/etc/nginx/conf.d/default.conf.template
rendered=/etc/nginx/conf.d/default.conf
legacy_root='root /var/www/html/public;'
current_root='root /var/www/html/current/public;'

render_config() {
    normalized=$(mktemp /tmp/extplorer-nginx-template.XXXXXX)
    temporary=$(mktemp /etc/nginx/conf.d/.default.conf.XXXXXX)
    cleanup() {
        rm -f "$normalized"
        if [ -n "$temporary" ]; then
            rm -f "$temporary"
        fi
    }
    trap cleanup EXIT HUP INT TERM

    if grep -Fq "$legacy_root" "$template"; then
        echo '[extplorer-nginx] migrating legacy document root to /var/www/html/current/public' >&2
        sed "s|$legacy_root|$current_root|g" "$template" > "$normalized"
    else
        cp "$template" "$normalized"
    fi

    if ! grep -Fq "$current_root" "$normalized"; then
        echo '[extplorer-nginx] template must use root /var/www/html/current/public;' >&2
        return 1
    fi

    sed "s/__EXTPLORER_UPLOAD_LIMIT__/${limit}m/g" "$normalized" > "$temporary"
    mv "$temporary" "$rendered"
    temporary=''
    trap - EXIT HUP INT TERM
    cleanup
}

limit=${EXTPLORER_UPLOAD_MAX_FILE_MB:-100}
case "$limit" in
    ''|*[!0-9]*|0) echo 'EXTPLORER_UPLOAD_MAX_FILE_MB must be a positive integer' >&2; exit 1 ;;
esac
if [ "$limit" -gt 10240 ]; then
    echo 'EXTPLORER_UPLOAD_MAX_FILE_MB must not exceed 10240 MB' >&2
    exit 1
fi

if [ "${1:-}" = '--render-config' ]; then
    render_config
    exit 0
fi

render_config
nginx -t
exec nginx -g 'daemon off;'
