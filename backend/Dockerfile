# syntax=docker/dockerfile:1

# Floating minor tag: rebuild with `docker compose build --pull` to pick up the newest PHP patch release.
ARG PHP_VERSION=8.5

FROM composer:2 AS composer

# Shared PHP-FPM base with the extensions the API needs. OPcache is built into PHP 8.5.
FROM php:${PHP_VERSION}-fpm AS base

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl pcntl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# Local development image. Source is bind-mounted by ../docker-compose.yml.
# The container user is mapped to the host user so bind-mounted files stay editable.
FROM base AS dev

ARG UID=1000
ARG GID=1000
RUN groupmod -o -g "${GID}" www-data && usermod -o -u "${UID}" -g "${GID}" www-data \
    && mkdir -p /home/www-data && usermod -d /home/www-data www-data \
    && chown -R www-data:www-data /var/www/html /home/www-data

# A writable home for tools that keep config or cache there (Tinker, Composer).
ENV HOME=/home/www-data

USER www-data

# Production image: optimised vendor, no dev dependencies.
FROM base AS production

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-dev

USER www-data
