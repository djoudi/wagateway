FROM php:8.3-fpm-alpine

#############################
# System Packages
#############################

RUN apk add --no-cache \
    git \
    curl \
    unzip \
    zip \
    bash \
    linux-headers \
    $PHPIZE_DEPS \
    postgresql-dev \
    oniguruma-dev \
    libxml2-dev \
    zlib-dev \
    libzip-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev

#############################
# PHP Extensions
#############################

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        sockets \
        xml \
        zip

#############################
# Redis Extension
#############################

RUN pecl install redis \
    && docker-php-ext-enable redis

#############################
# Composer
#############################

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

#############################
# PHP Configuration
#############################

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini $PHP_INI_DIR/conf.d/99-wagateway.ini

#############################
# User
#############################

RUN addgroup -g 1001 wagateway \
    && adduser -D -u 1001 -G wagateway wagateway

WORKDIR /var/www/html

#############################
# Install Composer Packages
#############################

COPY composer.json composer.lock* ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts

#############################
# Copy Application
#############################

COPY . .

#############################
# Optimize Laravel
#############################

RUN composer dump-autoload \
        --optimize \
        --no-dev \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R wagateway:wagateway /var/www/html \
    && chmod -R ug+rw storage bootstrap/cache

#############################
# Runtime
#############################

USER wagateway

EXPOSE 9000

CMD ["php-fpm"]
