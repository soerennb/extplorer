FROM php:8.3-fpm-alpine

# Install Dependencies
RUN apk add --no-cache \
    icu-dev \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    git \
    unzip

# Configure PHP Extensions
RUN docker-php-ext-install \
    intl \
    gd \
    zip \
    xml \
    mbstring \
    opcache

# Set Working Directory
WORKDIR /var/www/html

# Copy Application Code
# We copy everything, but .dockerignore should exclude local writable/ junk
COPY . .

# Install Composer Dependencies
# (In a real build pipeline, you might copy vendor from a build stage)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader

# Permissions
# Make writable directory writable by www-data (ID 82 in Alpine)
RUN chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
