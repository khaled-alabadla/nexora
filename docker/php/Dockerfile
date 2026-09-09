# syntax=docker/dockerfile:1
#
# Nexora backend image — PHP 8.4 (FPM).
# Used by the `app`, `queue`, and `scheduler` services.
#
FROM php:8.4-fpm-bookworm

# --- System packages -------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# --- PHP extensions ------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        intl \
        gd \
        zip \
        opcache \
        pcntl \
        exif

# Redis (PECL)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Xdebug — installed but disabled by default; enable via XDEBUG_MODE env.
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# --- Composer ----------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- PHP configuration -------------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-nexora.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

# --- Entrypoint ------------------------------------------------------------
COPY docker/php/entrypoint.sh /usr/local/bin/nexora-entrypoint
RUN chmod +x /usr/local/bin/nexora-entrypoint

# Align the in-container web user with the typical host UID/GID so bind-mounted
# files stay writable from both sides.
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g "${GID}" www-data \
    && usermod  -o -u "${UID}" -g "${GID}" www-data

WORKDIR /var/www/html

ENTRYPOINT ["nexora-entrypoint"]
CMD ["php-fpm"]
