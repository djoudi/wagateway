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
RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    zip \
    nodejs \
    npm \
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
# Install extensions one-by-one, skipping any already compiled into the
# base image (e.g. PDO became a core/always-on extension in PHP 8.4+ and
# cannot be built as a shared module). Checking php -m makes the build
# robust to future base-image changes.
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

# Image handling — gd needs a custom configure (freetype+jpeg).
RUN if ! php -m | grep -qi "^gd$"; then \
        docker-php-ext-configure gd --with-freetype --with-jpeg \
     && docker-php-ext-install gd; \
    fi
RUN if ! php -m | grep -qi "^exif$"; then docker-php-ext-install exif; fi

# XML family (dompdf: ext-dom; general: ext-xml, ext-simplexml).
# PHP 8.4+ dom uses the lexbor HTML5 parser, bundled as its own PHP
# extension (ext/lexbor) in the PHP source — it must be installed BEFORE
# dom (PHP_ADD_EXTENSION_DEP(dom, lexbor)). Do NOT use Alpine's lexbor-dev
# package: it is an older standalone library and fails to compile dom
# ("struct lxb_html_tree has no member named has_explicit_html_tag").
# xml/dom/simplexml/mbstring are compiled into the base PHP by default in
# some images, so skip any extension already reported by php -m.
RUN if ! php -m | grep -qi "lexbor"; then docker-php-ext-install lexbor; fi
RUN for ext in xml dom simplexml mbstring intl zip; do \
        if ! php -m | grep -qi "$ext"; then \
            docker-php-ext-install "$ext"; \
        fi; \
    done

# Redis extension — `pecl install redis` asks several interactive yes/no
# questions during configure (igbinary/lzf/zstd/msgpack serializer support).
# There is nothing on stdin to answer them during a non-interactive
# `docker build`, so it fails silently with exit code 1 and no useful
# error text. `yes ''` feeds it an endless stream of blank lines, which
# auto-accepts every prompt's default answer, however many there are.
RUN if ! php -m | grep -qi "^redis$"; then \
        yes '' | pecl install redis \
     && docker-php-ext-enable redis; \
    fi

# Non-root user (created before /var/log/php so it can be chowned there)
RUN addgroup -g 1001 wagateway && adduser -u 1001 -G wagateway -s /bin/sh -D wagateway

# ─────────────────────────────────────────────────────────────────────────
# PHP configuration
# ─────────────────────────────────────────────────────────────────────────
RUN mkdir -p /var/log/php \
 && chown -R wagateway:wagateway /var/log/php \
 && touch /var/log/php/error.log \
 && chown wagateway:wagateway /var/log/php/error.log \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-wagateway.ini"

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ─────────────────────────────────────────────────────────────────────────
# PHP dependencies
# ─────────────────────────────────────────────────────────────────────────
# A committed composer.lock pins the dependency tree (Laravel 13 / Livewire 4 /
# Filament 5). COMPOSER_MEMORY_LIMIT=-1 avoids the dependency solver hitting
# PHP's default memory limit on larger dependency trees (Filament pulls in a
# lot). -vvv surfaces composer's actual error text on failure — without it,
# build logs only show "exit code: N" with no explanation of what failed.
# COMPOSER_NO_AUDIT=1 skips Composer's advisory check, which is normal for any
# actively maintained framework and isn't specific to our version constraint.
# --no-scripts prevents artisan from running during install (no .env exists
# yet at build time), and the runtime entrypoint runs the Laravel boot
# commands once environment variables are actually available.
COPY composer.json composer.lock* ./
RUN COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_AUDIT=1 composer install \
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
