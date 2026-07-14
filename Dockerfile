# =========================================================================
# qrm.sg — Multi-Stage Dockerfile
# =========================================================================
# Stage 1: composer (PHP dependencies)
# Stage 2: frontend (Node.js / Vite build)
# Stage 3: runtime (nginx + php-fpm + redis + supervisor, single container)
# =========================================================================

# ---------------------------------------------------------------------------
# Stage 1: Composer dependencies
# ---------------------------------------------------------------------------
FROM php:8.4-cli-alpine AS composer

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       pdo_pgsql \
       gd \
       bcmath \
       intl \
       zip \
       pcntl \
       mbstring \
    && apk del $PHPIZE_DEPS linux-headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
COPY artisan ./
COPY database/ database/

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --ansi

# ---------------------------------------------------------------------------
# Stage 2: Frontend build (Node.js / Vite)
# ---------------------------------------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 3: Production runtime
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS runtime

LABEL maintainer="qrm.sg"

# System packages + PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    redis \
    postgresql-dev \
    postgresql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-libs \
    icu-dev \
    oniguruma-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       pdo_pgsql \
       gd \
       bcmath \
       intl \
       zip \
       opcache \
       pcntl \
       mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && mkdir -p /var/www/qrm.sg \
    && mkdir -p /run/nginx \
    && mkdir -p /var/log/supervisor

# Composer binary (for dev mode dependency installation)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# PHP config
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-qrm.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/qrm.ini

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ---------------------------------------------------------------------------
# Application code — baked into the image for production.
# In dev mode (docker-compose.override.yml), a bind-mount overlays this.
# ---------------------------------------------------------------------------
COPY . /var/www/qrm.sg
COPY --from=composer /app/vendor /var/www/qrm.sg/vendor
COPY --from=frontend /app/public/build /var/www/qrm.sg/public/build

# Ensure www-data owns storage + bootstrap/cache
RUN set -x \
    && addgroup -g 82 -S www-data 2>/dev/null || true \
    && adduser -u 82 -D -S -G www-data www-data 2>/dev/null || true \
    && chown -R www-data:www-data /var/www/qrm.sg \
    && chmod -R 775 /var/www/qrm.sg/storage /var/www/qrm.sg/bootstrap/cache

WORKDIR /var/www/qrm.sg

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
