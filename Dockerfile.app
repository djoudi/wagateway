FROM php:8.5-fpm-alpine AS base

# ─────────────────────────────────────────────────────────────────────────
# System dependencies
# ─────────────────────────────────────────────────────────────────────────
# - icu-dev:     required to compile ext-intl (Filament hard-requires this)
# - libxml2-dev: covers dom/simplexml (dompdf hard-requires ext-dom)
# - libzip-dev:  required to compile ext-zip — NOT the same as the zip/unzip
#                CLI utilities below; this is the C library ext-zip links
#                against, and its absence causes docker-php-ext-install to
#                fail silently with just "exit code: 2" and no clear message.
# - oniguruma-dev: required to compile ext-mbstring
# - lexbor-dev: required to compile ext-dom (PHP 8.4+ uses the lexbor HTML5
#   parser; without it the build fails with "lexbor/html/parser.h not found")
RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    zip \
    nodejs \
    npm \
    lexbor-dev \
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

# ─────────────────────────────────────────────────────────────────────────
# PHP extensions — installed in isolated groups rather than one combined
# command. If any single extension ever fails to compile, the build log
# points at the exact one-line RUN step that failed instead of a bundled
# command where the actual culprit is ambiguous. Every group below has
# been individually confirmed to build successfully.
# ─────────────────────────────────────────────────────────────────────────

# Core / database
# NOTE: tokenizer and ctype are deliberately NOT in this list. They are
# bundled/core PHP extensions — compiled directly into the PHP binary and
# enabled by default on every standard build, official Docker images
# included. They are not loadable modules and were never meant to be
# built via docker-php-ext-install: doing so fails because ext/tokenizer's
# source depends on Zend's own parser grammar file
# (Zend/zend_language_parser.y), which is consumed during PHP's own core
# build and isn't meant to be regenerated as a standalone module — this
# is exactly the "No rule to make target zend_language_parser.y" error.
RUN for ext in pdo_pgsql bcmath opcache pcntl sockets; do \
        if ! php -m | grep -qi "$ext"; then \
            docker-php-ext-install "$ext"; \
        fi; \
    done

# Defensive check: confirm the bundled extensions above are actually
# present on this base image. If a future PHP/Alpine release ever ships
# without them, this fails the build immediately with a clear message
# instead of surfacing as a confusing composer/runtime error much later.
RUN php -m | grep -qi '^tokenizer$' && php -m | grep -qi '^ctype$' \
 || (echo "ERROR: tokenizer or ctype missing from this PHP base image — they were expected to be bundled by default." && exit 1)

# Image handling
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd exif

# XML family (dompdf: ext-dom; general: ext-xml, ext-simplexml)
RUN docker-php-ext-install xml dom simplexml

# Multibyte strings (Laravel core requirement)
RUN docker-php-ext-install mbstring

# Internationalization (Filament hard-requires this)
RUN docker-php-ext-install intl

# Archive handling (Laravel file export/import features)
RUN docker-php-ext-install zip

# Redis extension — `pecl install redis` asks several interactive yes/no
# questions during configure (igbinary/lzf/zstd/msgpack serializer support).
# There is nothing on stdin to answer them during a non-interactive
# `docker build`, so it fails silently with exit code 1 and no useful
# error text. `yes ''` feeds it an endless stream of blank lines, which
# auto-accepts every prompt's default answer, however many there are.
RUN yes '' | pecl install redis \
 && docker-php-ext-enable redis

# ─────────────────────────────────────────────────────────────────────────
# PHP configuration
# ─────────────────────────────────────────────────────────────────────────
RUN mkdir -p /var/log/php \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-wagateway.ini"

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Non-root user
RUN addgroup -g 1001 wagateway && adduser -u 1001 -G wagateway -s /bin/sh -D wagateway

WORKDIR /var/www/html

# ─────────────────────────────────────────────────────────────────────────
# PHP dependencies
# ─────────────────────────────────────────────────────────────────────────
# NOTE: no composer.lock is committed yet — this project has never had a
# real `composer install` run against it before this deployment. This
# COPY + install resolves and generates the lock file fresh on first
# build. After your first successful build, copy composer.lock back out
# of the container and commit it, so future builds are fast and
# reproducible instead of re-resolving the full dependency tree each time:
#   docker cp <app-container-name>:/var/www/html/composer.lock ./composer.lock
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
RUN npm ci && npm run build \
 && composer dump-autoload --optimize --no-dev --no-scripts \
 && chown -R wagateway:wagateway /var/www/html \
 && chmod -R 775 storage bootstrap/cache

USER wagateway
EXPOSE 9000
CMD ["php-fpm"]
