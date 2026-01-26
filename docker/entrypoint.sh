#!/bin/sh
set -e

# 1. Handle Permissions (PUID/PGID)
# This is a basic implementation. For production, usermod might be needed.
# For now, we assume the container runs as root or a user with write access to the writable directory

# Define WRITEPATH for internal use
REAL_WRITEPATH=${WRITEPATH:-/var/www/html/writable}

# 2. Initial Setup / Auto-Config
if [ ! -f "$REAL_WRITEPATH/installed.lock" ]; then
    echo "First run detected..."
    
    # Create Admin User if ENV vars are present
    if [ ! -z "$EXTPLORER_ADMIN_USER" ] && [ ! -z "$EXTPLORER_ADMIN_PASS" ]; then
        echo "Creating admin user from environment variables..."
        # We can use a spark command or a temporary php script
        php spark install:create_user "$EXTPLORER_ADMIN_USER" "$EXTPLORER_ADMIN_PASS" --role admin --group Administrators || true
    fi

    touch "$REAL_WRITEPATH/installed.lock"
fi

# 3. Migrations & Maintenance
echo "Running migrations..."
php spark security:migrate || true

# 4. Start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
