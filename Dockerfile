FROM php:8.3-fpm-alpine AS builder

ARG APP_VERSION=dev

RUN apk add --no-cache \
        icu-libs \
        libpng \
        libzip \
        libxml2 \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        icu-dev \
        libpng-dev \
        libzip-dev \
        libxml2-dev \
        oniguruma-dev \
        git \
        unzip \
    && docker-php-ext-install \
        intl \
        gd \
        zip \
        xml \
        mbstring \
        opcache \
    && apk del .build-deps

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

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

FROM php:8.3-fpm-alpine

ARG APP_VERSION=dev
ENV EXTPLORER_IMAGE_VERSION=${APP_VERSION}

RUN apk add --no-cache \
        icu-libs \
        libpng \
        libzip \
        libxml2 \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        icu-dev \
        libpng-dev \
        libzip-dev \
        libxml2-dev \
        oniguruma-dev \
    && docker-php-ext-install \
        intl \
        gd \
        zip \
        xml \
        mbstring \
        opcache \
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
