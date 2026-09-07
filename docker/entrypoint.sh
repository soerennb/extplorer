#!/bin/sh

set -eu
set -o pipefail

REAL_WRITEPATH=${EXTPLORER_WRITE_PATH:-${WRITEPATH:-/var/www/html/writable}}
CODE_ROOT=${EXTPLORER_CODE_ROOT:-/var/www/html/current}
READINESS_FILE=${EXTPLORER_READINESS_FILE:-/tmp/extplorer-ready}
PHP_INI_SCAN_DIR=${PHP_INI_SCAN_DIR:-/usr/local/etc/php/conf.d}
PHP_RUNTIME_INI_DIR=${EXTPLORER_PHP_RUNTIME_INI_DIR:-/tmp/extplorer-php-conf.d}
SECRET_STAGING_ROOT=${EXTPLORER_SECRET_STAGING_ROOT:-/tmp/extplorer-secrets}
UPLOAD_LIMIT_MB=${EXTPLORER_UPLOAD_MAX_FILE_MB:-100}
MEMORY_LIMIT=${EXTPLORER_MEMORY_LIMIT:-256M}
MAX_EXECUTION_TIME=${EXTPLORER_MAX_EXECUTION_TIME:-120}
MAX_INPUT_TIME=${EXTPLORER_MAX_INPUT_TIME:-120}
STAGED_SECRET_FILES=""
STAGED_SECRET_RESULT=""
phase=preflight

log_event() {
    printf '[extplorer-init] phase=%s status=%s%s\n' "$1" "$2" "${3:+ $3}"
}

fail() {
    log_event "$phase" failed "code=$1 message=$2" >&2
    exit 1
}

cleanup_staged_secrets() {
    if [ -n "$STAGED_SECRET_FILES" ]; then
        for staged_file in $STAGED_SECRET_FILES; do
            rm -f "$staged_file" || true
        done
    fi
    if [ "$(id -u)" = "0" ] && [ -d "$SECRET_STAGING_ROOT" ]; then
        rmdir "$SECRET_STAGING_ROOT" 2>/dev/null || true
    fi
}

stage_secret_file() {
    source_file=$1
    secret_name=$2

    [ "$(id -u)" = "0" ] || return 0
    [ -n "$source_file" ] || return 0
    if [ ! -r "$source_file" ]; then
        fail secret_unreadable "Secret file is not readable: $source_file"
    fi

    mkdir -p "$SECRET_STAGING_ROOT"
    chown root:www-data "$SECRET_STAGING_ROOT"
    chmod 0710 "$SECRET_STAGING_ROOT"
    staged_file=$(mktemp "$SECRET_STAGING_ROOT/${secret_name}.XXXXXX") ||
        fail secret_staging_failed "Unable to create a temporary secret file"
    if ! cat "$source_file" > "$staged_file"; then
        rm -f "$staged_file" || true
        fail secret_staging_failed "Unable to stage secret file: $source_file"
    fi
    chmod 0400 "$staged_file"
    chown www-data:www-data "$staged_file"
    STAGED_SECRET_FILES="${STAGED_SECRET_FILES:+$STAGED_SECRET_FILES }$staged_file"
    STAGED_SECRET_RESULT=$staged_file
}

stage_configured_secrets() {
    [ "$(id -u)" = "0" ] || return 0

    if [ -n "${EXTPLORER_ADMIN_PASSWORD_FILE:-}" ]; then
        stage_secret_file "$EXTPLORER_ADMIN_PASSWORD_FILE" admin-password
        EXTPLORER_ADMIN_PASSWORD_FILE=$STAGED_SECRET_RESULT
        export EXTPLORER_ADMIN_PASSWORD_FILE
    fi
    if [ -n "${EXTPLORER_DB_PASSWORD_FILE:-}" ]; then
        stage_secret_file "$EXTPLORER_DB_PASSWORD_FILE" db-password
        EXTPLORER_DB_PASSWORD_FILE=$STAGED_SECRET_RESULT
        export EXTPLORER_DB_PASSWORD_FILE
    fi
    if [ -n "${EXTPLORER_REDIS_PASSWORD_FILE:-}" ]; then
        stage_secret_file "$EXTPLORER_REDIS_PASSWORD_FILE" redis-password
        EXTPLORER_REDIS_PASSWORD_FILE=$STAGED_SECRET_RESULT
        export EXTPLORER_REDIS_PASSWORD_FILE
    fi
    if [ -n "${EXTPLORER_ENCRYPTION_KEY_FILE:-}" ]; then
        stage_secret_file "$EXTPLORER_ENCRYPTION_KEY_FILE" encryption-key
        EXTPLORER_ENCRYPTION_KEY_FILE=$STAGED_SECRET_RESULT
        export EXTPLORER_ENCRYPTION_KEY_FILE
    fi
    if [ -n "${EXTPLORER_SMTP_PASSWORD_FILE:-}" ]; then
        stage_secret_file "$EXTPLORER_SMTP_PASSWORD_FILE" smtp-password
        EXTPLORER_SMTP_PASSWORD_FILE=$STAGED_SECRET_RESULT
        export EXTPLORER_SMTP_PASSWORD_FILE
    fi
    if [ -n "${EXTPLORER_FTPS_CA_FILE:-}" ]; then
        stage_secret_file "$EXTPLORER_FTPS_CA_FILE" ftps-ca
        EXTPLORER_FTPS_CA_FILE=$STAGED_SECRET_RESULT
        export EXTPLORER_FTPS_CA_FILE
    fi
}

on_exit() {
    status=$?
    cleanup_staged_secrets
    if [ "$status" -ne 0 ]; then
        rm -f "$READINESS_FILE" || true
        log_event "$phase" failed "exit_code=$status" >&2
    fi
}
trap on_exit EXIT

case "$UPLOAD_LIMIT_MB" in
    ''|*[!0-9]*) fail config_error 'EXTPLORER_UPLOAD_MAX_FILE_MB must be a positive integer' ;;
    0) fail config_error 'EXTPLORER_UPLOAD_MAX_FILE_MB must be greater than zero' ;;
esac
if [ "$UPLOAD_LIMIT_MB" -gt 10240 ]; then
    fail config_error 'EXTPLORER_UPLOAD_MAX_FILE_MB must not exceed 10240 MB'
fi

case "$MEMORY_LIMIT" in
    ''|*[!0-9KMGkmg]*) fail config_error 'EXTPLORER_MEMORY_LIMIT must be an integer with an optional K, M or G suffix' ;;
esac
memory_number=$MEMORY_LIMIT
case "$MEMORY_LIMIT" in
    *[KMGkmg]) memory_number=${MEMORY_LIMIT%?} ;;
esac
case "$memory_number" in
    ''|*[!0-9]*) fail config_error 'EXTPLORER_MEMORY_LIMIT must contain a numeric value' ;;
esac
case "$MAX_EXECUTION_TIME" in
    ''|*[!0-9]*) fail config_error 'EXTPLORER_MAX_EXECUTION_TIME must be a non-negative integer' ;;
esac
case "$MAX_INPUT_TIME" in
    ''|*[!0-9]*) fail config_error 'EXTPLORER_MAX_INPUT_TIME must be a non-negative integer' ;;
esac

phase=directories
mkdir -p \
    "$REAL_WRITEPATH" \
    "$REAL_WRITEPATH/config" \
    "$REAL_WRITEPATH/logs" \
    "$REAL_WRITEPATH/session" \
    "$REAL_WRITEPATH/uploads/temp" \
    "$REAL_WRITEPATH/uploads/shares" \
    "$REAL_WRITEPATH/uploads/chunks" \
    "${EXTPLORER_FILE_MANAGER_ROOT:-$REAL_WRITEPATH/file_manager_root}" \
    "$REAL_WRITEPATH/shared" \
    "$REAL_WRITEPATH/trash" \
    "$REAL_WRITEPATH/versions" \
    "$REAL_WRITEPATH/cache/thumbs" \
    "$REAL_WRITEPATH/cache/dav" \
    "$REAL_WRITEPATH/runtime" \
    "$REAL_WRITEPATH/backups"
mkdir -p "$PHP_RUNTIME_INI_DIR"
case ":$PHP_INI_SCAN_DIR:" in
    *":$PHP_RUNTIME_INI_DIR:"*) ;;
    *) PHP_INI_SCAN_DIR="$PHP_INI_SCAN_DIR:$PHP_RUNTIME_INI_DIR" ;;
esac
export PHP_INI_SCAN_DIR
if [ ! -w "$REAL_WRITEPATH" ]; then
    fail writable_path_not_writable "Persistent path is not writable: $REAL_WRITEPATH"
fi

if [ "$(id -u)" = "0" ]; then
    # The init service fixes existing trees recursively. Keep normal app
    # starts cheap by only assigning ownership to directories created here.
    chown www-data:www-data \
        "$REAL_WRITEPATH" \
        "$REAL_WRITEPATH/config" \
        "$REAL_WRITEPATH/logs" \
        "$REAL_WRITEPATH/session" \
        "$REAL_WRITEPATH/uploads" \
        "$REAL_WRITEPATH/uploads/temp" \
        "$REAL_WRITEPATH/uploads/shares" \
        "$REAL_WRITEPATH/uploads/chunks" \
        "${EXTPLORER_FILE_MANAGER_ROOT:-$REAL_WRITEPATH/file_manager_root}" \
        "$REAL_WRITEPATH/shared" \
        "$REAL_WRITEPATH/trash" \
        "$REAL_WRITEPATH/versions" \
        "$REAL_WRITEPATH/cache" \
        "$REAL_WRITEPATH/cache/thumbs" \
        "$REAL_WRITEPATH/cache/dav" \
        "$REAL_WRITEPATH/runtime" \
        "$REAL_WRITEPATH/backups"

    run_as_app() {
        su-exec www-data "$@"
    }
else
    run_as_app() {
        "$@"
    }
fi

stage_configured_secrets

phase=code
if [ ! -f "$CODE_ROOT/spark" ] || [ ! -d "$CODE_ROOT/app" ] || [ ! -d "$CODE_ROOT/public" ]; then
    fail code_release_missing "Active code release is incomplete: $CODE_ROOT"
fi
cd "$CODE_ROOT"
rm -f "$READINESS_FILE"

phase=migration
run_as_app php spark security:migrate

# Validate every selected persistent backend before the runtime process is
# started. CodeIgniter may otherwise fall back to a dummy cache handler when a
# remote service is unavailable, which would make a broken deployment appear
# healthy.
phase=storage-check
run_as_app php spark storage:check

phase=config
if [ ! -f "$REAL_WRITEPATH/installed.lock" ] || [ "${EXTPLORER_APPLY_ENV:-0}" = "1" ]; then
    run_as_app php /usr/local/bin/apply-env-settings.php
fi

phase=admin-bootstrap
run_as_app php spark admin:bootstrap

phase=runtime-config
cat > "$PHP_RUNTIME_INI_DIR/zz-extplorer-runtime.ini" <<EOF
upload_max_filesize = ${UPLOAD_LIMIT_MB}M
post_max_size = ${UPLOAD_LIMIT_MB}M
memory_limit = ${MEMORY_LIMIT}
max_execution_time = ${MAX_EXECUTION_TIME}
max_input_time = ${MAX_INPUT_TIME}
EOF

if ! printf '%s\n' "${EXTPLORER_IMAGE_VERSION:-unknown}" > "$READINESS_FILE"; then
    fail readiness_marker_unwritable "Unable to write readiness marker: $READINESS_FILE"
fi
chmod 0644 "$READINESS_FILE"
log_event complete success "image_version=${EXTPLORER_IMAGE_VERSION:-unknown}"

if [ "$(id -u)" = "0" ]; then
    # Secret overlays may start this wrapper as root so chmod-600 host secret
    # files remain usable. The long-running PHP-FPM process is still non-root.
    exec su-exec www-data php-fpm -F
fi
exec php-fpm -F
