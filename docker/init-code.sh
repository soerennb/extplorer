#!/bin/sh
set -e

SOURCE_ROOT=${SOURCE_ROOT:-/app}
TARGET_ROOT=${TARGET_ROOT:-/var/www/html}
IMAGE_VERSION_FILE=${IMAGE_VERSION_FILE:-/image-version}
TARGET_VERSION_FILE="${TARGET_ROOT}/.image-version"

if [ -f "${IMAGE_VERSION_FILE}" ]; then
    IMAGE_VERSION=$(cat "${IMAGE_VERSION_FILE}")
else
    IMAGE_VERSION=${EXTPLORER_IMAGE_VERSION:-unknown}
fi

should_refresh=0
if [ "${EXTPLORER_CODE_FORCE:-0}" = "1" ]; then
    should_refresh=1
fi

if [ ! -f "${TARGET_VERSION_FILE}" ]; then
    should_refresh=1
else
    TARGET_VERSION=$(cat "${TARGET_VERSION_FILE}" 2>/dev/null || true)
    if [ "${TARGET_VERSION}" != "${IMAGE_VERSION}" ]; then
        should_refresh=1
    fi
fi

if [ "${should_refresh}" -eq 1 ]; then
    echo "Initializing code volume (version ${IMAGE_VERSION})..."
    mkdir -p "${TARGET_ROOT}"
    find "${TARGET_ROOT}" -mindepth 1 -maxdepth 1 ! -name writable -exec rm -rf {} +
    tar -C "${SOURCE_ROOT}" -cf - --exclude=./writable . | tar -C "${TARGET_ROOT}" -xf -
    echo "${IMAGE_VERSION}" > "${TARGET_VERSION_FILE}"
else
    echo "Code volume already up to date (version ${IMAGE_VERSION})."
fi
