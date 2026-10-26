FROM php:8.5.12-fpm-alpine3.24 AS builder

ARG APP_VERSION=dev

# Keep fixed Brotli, curl and nghttp2 versions in both PHP stages.
# CVE-2026-111497, CVE-2026-92392 and CVE-2026-58055.
RUN apk add --no-cache \
        ca-certificates \
        'brotli-libs>=1.2.1-r0' \
        'curl>=8.23.0-r0' \
        icu-libs \
        libpng \
        libzip \
        'nghttp2-libs>=1.70.0-r0' \
    && apk add --no-cache --virtual .build-deps \
        icu-dev \
        libpng-dev \
        libzip-dev \
        git \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        gd \
        zip \
    && apk del .build-deps

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2.10.3 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY app app
COPY public public
COPY resources resources
COPY spark spark
COPY env env
COPY nginx.conf.example nginx.conf.example
COPY preload.php preload.php
COPY .htaccess .htaccess
RUN echo "${APP_VERSION}" > /image-version
RUN find /app -type f ! -path '/app/writable/*' -print0 \
    | sort -z \
    | xargs -0 sha256sum \
    | sha256sum \
    | awk '{print $1}' > /image-content-sha256

FROM php:8.5.12-fpm-alpine3.24

ARG APP_VERSION=dev
ENV EXTPLORER_IMAGE_VERSION=${APP_VERSION}

# Keep these security minimums aligned with the builder stage.
RUN apk add --no-cache \
        ca-certificates \
        'brotli-libs>=1.2.1-r0' \
        'curl>=8.23.0-r0' \
        icu-libs \
        libpng \
        libzip \
        'nghttp2-libs>=1.70.0-r0' \
        sqlite-libs \
        su-exec \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libpng-dev \
        libzip-dev \
        openssl-dev \
        sqlite-dev \
        mariadb-connector-c-dev \
    && cd /tmp \
    && printf '\n\n\n\n\n' | pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure ftp --with-ftp-ssl \
    && docker-php-ext-install -j"$(nproc)" \
        ftp \
        intl \
        gd \
        zip \
        mysqli \
        pdo_mysql \
        pdo_sqlite \
    && apk del .build-deps \
    && php -r 'if (!function_exists("ftp_ssl_connect")) { exit(1); }'

RUN sed -i -e 's#^error_log = /proc/self/fd/2#error_log = /var/www/html/writable/logs/php-fpm.log#' \
    -e 's#^access.log = /proc/self/fd/2#access.log = /var/www/html/writable/logs/php-fpm-access.log#' \
    /usr/local/etc/php-fpm.d/docker.conf

RUN mkdir -p /var/www/html /app

COPY --from=builder /app /app
COPY --from=builder /image-version /image-version
COPY --from=builder /image-content-sha256 /image-content-sha256

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY examples/auth /usr/local/share/extplorer-auth-examples
COPY docker/init-code.sh /usr/local/bin/init-code
COPY docker/apply-env-settings.php /usr/local/bin/apply-env-settings.php
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/init-code

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
