FROM php:8.3-fpm-alpine AS base

# System deps
RUN apk add --no-cache \
    postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    zip unzip git curl oniguruma-dev libxml2-dev \
    linux-headers $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    pdo pdo_pgsql gd bcmath mbstring xml tokenizer ctype \
    opcache pcntl sockets

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
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && chown -R wagateway:wagateway /var/www/html \
 && chmod -R 755 storage bootstrap/cache

USER wagateway
EXPOSE 9000
CMD ["php-fpm"]
