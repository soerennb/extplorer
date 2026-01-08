#!/bin/bash

# eXtplorer 3 Build Script
# Creates a deployable ZIP archive

APP_NAME="extplorer3"
VERSION=$(php -r "echo include 'app/Config/App.php'; \$app = new Config\App(); echo \$app->version;")
BUILD_DIR="builds/release"
ZIP_NAME="builds/${APP_NAME}-${VERSION}.zip"

echo "Building ${APP_NAME} version ${VERSION}..."

# 1. Cleanup old builds
rm -rf builds/*
mkdir -p ${BUILD_DIR}

# 2. Install production dependencies
echo "Installing production dependencies..."
php composer.phar install --no-dev --optimize-autoloader --quiet

# 3. Copy files to build directory
echo "Copying files..."
cp -r app ${BUILD_DIR}/
cp -r public ${BUILD_DIR}/
cp -r vendor ${BUILD_DIR}/
cp -r LICENSE ${BUILD_DIR}/
cp -r README.md ${BUILD_DIR}/
cp composer.json ${BUILD_DIR}/

# 4. Create necessary writable structure
mkdir -p ${BUILD_DIR}/writable/cache/thumbs
mkdir -p ${BUILD_DIR}/writable/logs
mkdir -p ${BUILD_DIR}/writable/session
mkdir -p ${BUILD_DIR}/writable/uploads
mkdir -p ${BUILD_DIR}/writable/file_manager_root
touch ${BUILD_DIR}/writable/users.json
echo "[]" > ${BUILD_DIR}/writable/users.json

# Copy .htaccess to root if needed (CI4 usually uses public/ as root)
# If you want to support "root folder" deployment:
cp public/.htaccess ${BUILD_DIR}/.htaccess 2>/dev/null || true
cp public/index.php ${BUILD_DIR}/index.php 2>/dev/null || true

# 5. Create ZIP
echo "Creating ZIP archive..."
cd ${BUILD_DIR}
zip -r "../../${ZIP_NAME}" . -x "*.git*" -x "node_modules*"
cd ../..

# 6. Reinstall dev dependencies for local development
echo "Restoring dev dependencies..."
php composer.phar install --quiet

echo "Done! Archive created: ${ZIP_NAME}"
