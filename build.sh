#!/bin/bash

# eXtplorer 3 Build Script
# Creates a deployable archive

set -e

APP_NAME="extplorer3"
VERSION=$(grep 'public string $version' app/Config/App.php | sed -E "s/.*= '(.+)';/\1/")
BUILD_DIR="builds/release"
TAR_NAME="builds/${APP_NAME}-${VERSION}.tar.gz"
ZIP_NAME="builds/${APP_NAME}-${VERSION}.zip"

echo "Building ${APP_NAME} version ${VERSION}..."

# 1. Cleanup old builds
rm -rf builds/*
mkdir -p ${BUILD_DIR}

# 2. Install production dependencies
echo "Installing production dependencies..."
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --quiet
elif [ -f "composer.phar" ]; then
    php composer.phar install --no-dev --optimize-autoloader --quiet
else
    echo "Error: composer not found"
    exit 1
fi

# 3. Copy files to build directory
echo "Copying files..."
cp -r app ${BUILD_DIR}/
cp -r public ${BUILD_DIR}/
cp -r vendor ${BUILD_DIR}/
cp LICENSE ${BUILD_DIR}/
cp README.md ${BUILD_DIR}/
cp composer.json ${BUILD_DIR}/
cp .htaccess ${BUILD_DIR}/

# 4. Create necessary writable structure
mkdir -p ${BUILD_DIR}/writable/cache/thumbs
mkdir -p ${BUILD_DIR}/writable/logs
mkdir -p ${BUILD_DIR}/writable/session
mkdir -p ${BUILD_DIR}/writable/uploads
mkdir -p ${BUILD_DIR}/writable/file_manager_root
mkdir -p ${BUILD_DIR}/writable/shared
mkdir -p ${BUILD_DIR}/writable/trash
touch ${BUILD_DIR}/writable/users.json
echo "[]" > ${BUILD_DIR}/writable/users.json

# Copy index.php to root if needed (CI4 usually uses public/ as root)
cp public/index.php ${BUILD_DIR}/index.php 2>/dev/null || true

# 5. Create archives
echo "Creating archives..."
cd ${BUILD_DIR}
tar -czf "../../${TAR_NAME}" .

if command -v zip >/dev/null 2>&1; then
    zip -r "../../${ZIP_NAME}" . -x "*.git*" -x "node_modules*"
else
    echo "Warning: zip command not found, skipping ZIP creation."
fi
cd ../..

# 6. Reinstall dev dependencies for local development
echo "Restoring dev dependencies..."
if command -v composer >/dev/null 2>&1; then
    composer install --quiet
elif [ -f "composer.phar" ]; then
    php composer.phar install --quiet
fi

echo "Done! Archive created: ${TAR_NAME}"
if [ -f "${ZIP_NAME}" ]; then
    echo "Archive created: ${ZIP_NAME}"
fi