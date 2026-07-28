FROM php:8.3-fpm-alpine AS base

# System deps
# - icu-dev:   required to compile ext-intl (Filament hard-requires this)
# - libxml2-dev: covers dom/simplexml (dompdf hard-requires ext-dom)
# - libzip-dev: required to compile ext-zip — NOT the same as the zip/unzip
#   CLI utilities below; this is the C library ext-zip links against, and
#   its absence is a well-known cause of docker-php-ext-install failing
#   silently with just "exit code: 2" and no clearer message.
RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    zip \
    linux-headers \
    $PHPIZE_DEPS \
    postgresql-dev \
    icu-dev \
    icu-libs \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev

# PHP extensions — installed in isolated groups rather than one combined
# command. If any single extension ever fails to compile again, the build
# log will point at the exact one-line RUN step that failed instead of a
# bundled 12-extension command where the actual culprit is ambiguous.

# Core / database — proven working in prior builds
# Configure GD
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Install PHP extensions
RUN docker-php-ext-install \
    bcmath \
    exif \
    intl \
    zip \
    gd \
    pdo_pgsql \
    opcache \
    pcntl \
    sockets

# Install Redis
RUN pecl install redis \
 && docker-php-ext-enable redis

# XML family (dompdf: ext-dom; general: ext-xml, ext-simplexml)
RUN docker-php-ext-install xml dom simplexml

# Multibyte strings (Laravel core requirement)
RUN docker-php-ext-install mbstring

# Internationalization (Filament hard-requires this)
RUN docker-php-ext-install intl

# Archive handling (Laravel file export/import features)
RUN docker-php-ext-install zip

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
 && chmod -R 775 storage bootstrap/cache

USER wagateway
EXPOSE 9000
CMD ["php-fpm"]
