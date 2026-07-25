FROM php:8.3-fpm-alpine AS base

# System deps — icu-dev is required to compile ext-intl (Filament hard-requires
# this); libxml2-dev covers dom/simplexml (dompdf hard-requires ext-dom).
RUN apk add --no-cache \
    postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    icu-dev icu-libs \
    zip unzip git curl oniguruma-dev libxml2-dev \
    linux-headers $PHPIZE_DEPS

# PHP extensions
# - intl:      hard-required by filament/support (Filament v3)
# - dom, simplexml: hard-required by dompdf/dompdf (barryvdh/laravel-dompdf)
# - zip:       Laravel file export/import features
# - exif:      image metadata handling (dompdf, media processing)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    pdo pdo_pgsql gd bcmath mbstring xml dom simplexml tokenizer ctype \
    opcache pcntl sockets intl zip exif

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-wagateway.ini"

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create non-root user
RUN addgroup -g 1001 wagateway && adduser -u 1001 -G wagateway -s /bin/sh -D wagateway

WORKDIR /var/www/html

# Install PHP dependencies
# NOTE: no composer.lock is committed yet — this project has never had a
# real `composer install` run against it. This COPY + install will resolve
# and generate the lock file fresh on first build. After your first
# successful build, copy composer.lock back out of the container and
# commit it, so future builds are reproducible instead of re-resolving:
#   docker cp wg_app:/var/www/html/composer.lock ./composer.lock
#
# COMPOSER_MEMORY_LIMIT=-1 avoids the dependency solver hitting PHP's
# default memory limit on larger dependency trees (Filament pulls in a lot).
# -vvv surfaces composer's actual error text on failure — without it,
# build logs only show "exit code: N" with no explanation of what failed.
COPY composer.json composer.lock* ./
RUN COMPOSER_MEMORY_LIMIT=-1 composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    -vvv

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && chown -R wagateway:wagateway /var/www/html \
 && chmod -R 755 storage bootstrap/cache

USER wagateway
EXPOSE 9000
CMD ["php-fpm"]
