#!/bin/sh

set -eu
set -o pipefail

SOURCE_ROOT=${SOURCE_ROOT:-/app}
TARGET_ROOT=${TARGET_ROOT:-/var/www/html}
IMAGE_VERSION_FILE=${IMAGE_VERSION_FILE:-/image-version}
CONTENT_HASH_FILE=${CONTENT_HASH_FILE:-/image-content-sha256}
RELEASES_ROOT="${TARGET_ROOT}/.releases"
CURRENT_LINK="${TARGET_ROOT}/current"
WRITABLE_ROOT="${TARGET_ROOT}/writable"

log_event() {
    printf '[extplorer-init-code] phase=%s status=%s%s\n' "$1" "$2" "${3:+ $3}"
}

fail() {
    log_event "$1" failed "code=$2 message=$3" >&2
    exit 1
}

if [ -f "$IMAGE_VERSION_FILE" ]; then
    IMAGE_VERSION=$(tr -d '\r\n' < "$IMAGE_VERSION_FILE")
else
    IMAGE_VERSION=${EXTPLORER_IMAGE_VERSION:-unknown}
fi
if [ -z "$IMAGE_VERSION" ]; then
    fail identity invalid_image_version 'Image version is empty'
fi

if [ -f "$CONTENT_HASH_FILE" ]; then
    CONTENT_HASH=$(tr -d '\r\n' < "$CONTENT_HASH_FILE")
else
    CONTENT_HASH=$(find "$SOURCE_ROOT" -type f ! -path '*/writable/*' -print0 | sort -z | xargs -0 sha256sum | sha256sum | awk '{print $1}')
fi
if [ -z "$CONTENT_HASH" ]; then
    fail identity missing_content_hash 'Unable to determine image content identity'
fi

safe_version=$(printf '%s' "$IMAGE_VERSION" | tr -c 'A-Za-z0-9._-' '-')
release_id="${safe_version}-${CONTENT_HASH}"

activate_release() {
    release_id_arg=$1
    release_path="${RELEASES_ROOT}/${release_id_arg}"
    if [ ! -f "$release_path/spark" ] || [ ! -d "$release_path/app" ] || [ ! -d "$release_path/public" ]; then
        fail activate invalid_release 'Requested release is incomplete'
    fi
    temporary_link="${TARGET_ROOT}/.current-link.$$"
    rm -f "$temporary_link"
    ln -s ".releases/${release_id_arg}" "$temporary_link"
    # BusyBox mv follows an existing symlink unless -T is used. Without this
    # flag an update silently places the temporary link inside the old release
    # directory and leaves current pointing at stale code.
    mv -fT "$temporary_link" "$CURRENT_LINK"
    # Docker volumes are commonly created as world-writable directories. On
    # hosts with protected_symlinks enabled, a root-owned link in such a
    # directory cannot be followed by the non-root runtime user.
    if [ "$(id -u)" = '0' ]; then
        chown -h www-data:www-data "$CURRENT_LINK"
    fi
    log_event activate success "release=${release_id_arg}"
}

make_release_readable() {
    release_path_arg=$1
    if [ "$(id -u)" = '0' ]; then
        # The runtime image uses Alpine's www-data account (UID 82). Apply
        # ownership only to the newly activated release; the persistent code
        # volume therefore remains cheap to start and safe to mount read-only.
        chown -R www-data:www-data "$release_path_arg"
    fi
}

if [ "${1:-}" = '--rollback' ]; then
    [ -n "${2:-}" ] || fail rollback missing_release 'A release id is required'
    mkdir -p "$RELEASES_ROOT"
    activate_release "$2"
    make_release_readable "${RELEASES_ROOT}/${2}"
    exit 0
fi

phase=directories
mkdir -p "$TARGET_ROOT" "$RELEASES_ROOT"
if [ "$(id -u)" = '0' ]; then
    # Runtime containers must be able to traverse the release directory even
    # though only the init container performs release activation.
    chown www-data:www-data "$RELEASES_ROOT"
    chmod 755 "$RELEASES_ROOT"
fi
if [ ! -e "$CURRENT_LINK" ] && [ -d "$TARGET_ROOT/app" ] && [ -d "$TARGET_ROOT/public" ]; then
    legacy_release="legacy-$(date +%Y%m%d%H%M%S)"
    legacy_stage="${RELEASES_ROOT}/.${legacy_release}.staging"
    mkdir -p "$legacy_stage"
    tar -C "$TARGET_ROOT" -cf - --exclude=./writable --exclude=./.releases --exclude=./current . | tar -C "$legacy_stage" -xf -
    mv "$legacy_stage" "${RELEASES_ROOT}/${legacy_release}"
    activate_release "$legacy_release"
    log_event legacy success "release=${legacy_release}"
fi

if [ -d "$WRITABLE_ROOT" ] && [ "$(id -u)" = '0' ]; then
    owner_marker="${WRITABLE_ROOT}/.extplorer-owner"
    if [ ! -f "$owner_marker" ] || [ "${EXTPLORER_FIX_PERMISSIONS:-0}" = '1' ]; then
        chown -R www-data:www-data "$WRITABLE_ROOT"
        printf '%s\n' 'www-data' > "$owner_marker"
    fi
fi

if [ -e "$CURRENT_LINK" ] && [ "${EXTPLORER_CODE_FORCE:-0}" != '1' ]; then
    current_release=$(readlink "$CURRENT_LINK" 2>/dev/null || true)
    if [ "$current_release" = ".releases/${release_id}" ] && [ -f "${RELEASES_ROOT}/${release_id}/.release-version" ]; then
        log_event sync success "release=${release_id} changed=0"
        exit 0
    fi
fi

# Never replace or delete a release that may still be the active target. A
# forced restage gets a unique generation suffix so a failed update leaves the
# previous current release intact.
if [ -e "${RELEASES_ROOT}/${release_id}" ]; then
    release_id="${release_id}-$(date +%Y%m%d%H%M%S)-$$"
fi

phase=stage
stage_directory=$(mktemp -d "${RELEASES_ROOT}/.staging.XXXXXX")
cleanup_stage() {
    rm -rf "$stage_directory" || true
}
trap cleanup_stage EXIT

tar -C "$SOURCE_ROOT" -cf - --exclude=./writable . | tar -C "$stage_directory" -xf -
printf '%s\n' "$IMAGE_VERSION" > "$stage_directory/.release-version"
printf '%s\n' "$CONTENT_HASH" > "$stage_directory/.release-content-hash"

phase=validate
for required in spark app/Config/Paths.php public/index.php; do
    [ -e "$stage_directory/$required" ] || fail validate missing_file "Release is missing ${required}"
done
find "$stage_directory/app" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null

phase=activate
release_path="${RELEASES_ROOT}/${release_id}"
if [ -e "$release_path" ]; then
    fail activate release_collision "Release target already exists: ${release_id}"
fi
mv "$stage_directory" "$release_path"
activate_release "$release_id"
make_release_readable "$release_path"
trap - EXIT

log_event sync success "release=${release_id} changed=1 version=${IMAGE_VERSION}"
