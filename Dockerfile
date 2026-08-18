FROM php:8.5.9-fpm-alpine3.24 AS builder

ARG APP_VERSION=dev

RUN apk add --no-cache \
        icu-libs \
        libpng \
        libzip \
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
COPY --from=composer:2.10.2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY app app
COPY public public
COPY spark spark
COPY env env
COPY nginx.conf.example nginx.conf.example
COPY preload.php preload.php
COPY .htaccess .htaccess
COPY writable writable

RUN echo "${APP_VERSION}" > /image-version

FROM php:8.5.9-fpm-alpine3.24

ARG APP_VERSION=dev
ENV EXTPLORER_IMAGE_VERSION=${APP_VERSION}

RUN apk add --no-cache \
        icu-libs \
        libpng \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        icu-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        gd \
        zip \
    && apk del .build-deps

RUN mkdir -p /var/www/html /app

COPY --from=builder /app /app
COPY --from=builder /image-version /image-version

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/init-code.sh /usr/local/bin/init-code
COPY docker/apply-env-settings.php /usr/local/bin/apply-env-settings.php
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/init-code

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
