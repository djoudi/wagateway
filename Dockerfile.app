FROM php:8.5-fpm-alpine AS base

# ─────────────────────────────────────────────────────────────────────────
# Core runtime (must succeed — nginx/supervisor live here)
# Debian font names (fonts-noto-color-emoji) do not exist on Alpine and
# would abort this entire apk add, so Chromium/fonts are a separate step.
# ─────────────────────────────────────────────────────────────────────────
RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    zip \
    nginx \
    supervisor \
    nodejs \
    npm \
    su-exec \
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
    libpng-dev \
 && nginx -v \
 && command -v supervisord

# Chromium for whatsapp-web.js / Puppeteer (Alpine package names).
RUN apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ttf-freefont \
    font-noto \
    font-noto-emoji

# ─────────────────────────────────────────────────────────────────────────
# PHP extensions (same groups as the multi-container build)
# ─────────────────────────────────────────────────────────────────────────
RUN for ext in pdo_pgsql bcmath opcache pcntl sockets; do \
        if ! php -m | grep -qi "$ext"; then \
            docker-php-ext-install "$ext"; \
        fi; \
    done

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

RUN if ! php -m | grep -qi "^redis$"; then \
        yes '' | pecl install redis \
     && docker-php-ext-enable redis; \
    fi

# ─────────────────────────────────────────────────────────────────────────
# PHP configuration
# ─────────────────────────────────────────────────────────────────────────
RUN mkdir -p /var/log/php \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-wagateway.ini"
# Use our dedicated pool (workers run as wagateway to match file ownership).
# Overwrite zz-docker.conf so the stock [www] pool does not also bind :9000.
COPY docker/php-fpm-www.conf /usr/local/etc/php-fpm.d/www.conf
RUN printf '[global]\ndaemonize = no\n' > /usr/local/etc/php-fpm.d/zz-docker.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Non-root user
RUN addgroup -g 1001 wagateway && adduser -u 1001 -G wagateway -s /bin/sh -D wagateway

WORKDIR /var/www/html

# ─────────────────────────────────────────────────────────────────────────
# PHP dependencies (Laravel)
# ─────────────────────────────────────────────────────────────────────────
COPY composer.json composer.lock* ./
RUN COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_AUDIT=1 composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    -vvv

# ─────────────────────────────────────────────────────────────────────────
# Application source (before builds so both composer dump-autoload and the
# Vite build see the real code; public/build from the build context is
# excluded via .dockerignore so the image-built assets are not overwritten)
# ─────────────────────────────────────────────────────────────────────────
COPY . .

# ─────────────────────────────────────────────────────────────────────────
# Frontend (Vite) — build now so the manifest exists at runtime
# ─────────────────────────────────────────────────────────────────────────
RUN npm ci && npm run build

# ─────────────────────────────────────────────────────────────────────────
# wa-service (Node.js WhatsApp service)
# ─────────────────────────────────────────────────────────────────────────
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
RUN cd wa-service && npm install --omit=dev
RUN mkdir -p wa-service/sessions wa-service/src/logs

# ─────────────────────────────────────────────────────────────────────────
# Runtime layout & permissions
# ─────────────────────────────────────────────────────────────────────────
RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && chown -R wagateway:wagateway /var/www/html \
 && chmod -R 775 storage bootstrap/cache \
 && mkdir -p /var/log/nginx /run/nginx \
 && chown -R wagateway:wagateway /var/log/nginx /run/nginx /var/log/php \
 && touch /var/log/php/error.log \
 && chown wagateway:wagateway /var/log/php/error.log

# Nginx + Supervisor config
COPY docker/nginx.main.conf /etc/nginx/nginx.conf
COPY docker/nginx.single.conf /etc/nginx/http.d/default.conf
RUN mkdir -p /etc/nginx/conf.d /var/log/nginx /run \
 && rm -f /etc/nginx/http.d/default.conf.bak \
 && nginx -t
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/run-artisan.sh /usr/local/bin/run-artisan
COPY docker/run-wa-service.sh /usr/local/bin/run-wa-service
RUN chmod +x /entrypoint.sh /usr/local/bin/run-artisan /usr/local/bin/run-wa-service /var/www/html/artisan \
 && mkdir -p /var/log/supervisor /home/wagateway \
 && chown -R wagateway:wagateway /var/log/supervisor /var/log/nginx /run/nginx /var/log/php /home/wagateway \
 && chmod 777 /var/log/supervisor

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=5 \
    CMD curl -fsS http://127.0.0.1/up >/dev/null || exit 1

ENTRYPOINT ["/entrypoint.sh"]
