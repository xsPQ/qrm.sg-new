# syntax=docker/dockerfile:1

# =============================================================================
# qrm.sg production application image.
#
# Multi-stage build that fixes DEV-645 / DEV-688: the production image must
# contain the Vite-built frontend (public/build/manifest.json + hashed assets),
# which previously never made it into the image and caused 500s on every
# @vite() Blade page.
#
#   Stage 1  frontend   - compiles Vite assets with Node (never shipped)
#   Stage 2  production - PHP-FPM runtime with vendor + built assets
#
# Build:
#   docker build -f docker/app/Dockerfile --target production -t qrm-sg:latest .
#
# The CI docker-image job builds this file with --target production
# (see .github/workflows/ci.yml).
# =============================================================================

# ---------------------------------------------------------------------------
# Stage 1 - Frontend assets (Vite)
# ---------------------------------------------------------------------------
# NODE_ENV is intentionally left unset so devDependencies (vite, tailwindcss,
# laravel-vite-plugin, ...) are installed. The production image never ships Node.
FROM node:22-alpine AS frontend
WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-fund --no-audit

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build
# -> /app/public/build/manifest.json
# -> /app/public/build/assets/*

# ---------------------------------------------------------------------------
# Stage 2 - Production runtime (PHP-FPM)
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS production

# System libraries + PHP extension build toolchain.
# The *-dev headers are retained so the compiled shared extensions stay
# loadable at runtime; build-base + autoconf guarantee the pecl (redis) build
# succeeds reproducibly.
RUN apk add --no-cache \
        postgresql-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        libzip-dev \
        icu-dev \
        curl-dev \
        unzip \
        git \
        curl \
        fcgi \
        autoconf \
        build-base \
        su-exec \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        gd \
        mbstring \
        dom \
        xml \
        zip \
        bcmath \
        opcache \
        pcntl \
        intl \
        curl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Composer binary (pinned via the official multi-stage image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Production PHP runtime configuration + opcache tuning.
# sys_temp_dir is set explicitly (DEV-858 recommendation #2): when a target dir
# is ever momentarily unwritable, PHP's tempnam() falls back here, and an
# unconfigured sys_temp_dir can itself emit the warning Laravel turns into a
# 500. /tmp is world-writable and the standard choice on Alpine.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'opcache.enable=1' \
        'opcache.memory_consumption=192' \
        'opcache.interned_strings_buffer=16' \
        'opcache.max_accelerated_files=20000' \
        'opcache.validate_timestamps=0' \
        'opcache.save_comments=1' \
        'sys_temp_dir = "/tmp"' \
        'upload_tmp_dir = "/tmp"' \
        > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www

# Composer dependencies (cache-friendly layer, copied before the source).
# --no-scripts is required: the post-autoload-dump hook invokes artisan which
# cannot run before the application source (artisan/config/app) is present.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --optimize-autoloader

# Application source.
COPY . .

# Frontend build artefacts from stage 1. This is the DEV-645 fix: the Vite
# manifest + hashed assets must live in the production image so @vite() resolves.
COPY --from=frontend /app/public/build /var/www/public/build

# Deferred post-install + filesystem setup.
# package:discover writes bootstrap/cache/packages.php from
# vendor/composer/installed.json and needs neither .env nor a database. The
# host-shipped public/storage symlink points to an absolute host path, so it is
# recreated with a container-relative target; storage/bootstrap cache must be
# writable by the www-data user that runs php-fpm.
#
# NOTE (DEV-858): the chown/chmod here only applies at BUILD time. At runtime a
# host bind-mount on storage/, or files written by a root-run worker/scheduler,
# can reset ownership to root and break tempnam()/view compilation with HTTP
# 500 on every Blade page. The authoritative runtime self-heal lives in
# docker/app/entrypoint.sh (see ENTRYPOINT below), which re-normalises
# storage/ + bootstrap/cache ownership on every container start.
RUN php artisan package:discover --ansi \
    && rm -f public/storage \
    && ln -s ../storage/app/public public/storage \
    && mkdir -p storage/app/public \
                storage/framework/cache/data \
                storage/framework/sessions \
                storage/framework/views \
                storage/logs \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Runtime entrypoint (DEV-858): re-establishes www-data ownership on
# storage/ + bootstrap/cache at every start and drops worker/scheduler to
# www-data so no root-owned files reappear. php-fpm stays root to manage its
# www-data pool.
COPY docker/app/entrypoint.sh /usr/local/bin/docker-qrm-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-qrm-entrypoint.sh

EXPOSE 9000

# Liveness probe: php-fpm accepts FastCGI connections on :9000. Uses the php
# binary shipped in the base image so no extra runtime dependency is needed.
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php -r '$s=@fsockopen("127.0.0.1",9000,$e,$m,2);if($s){fclose($s);exit(0);}exit(1);'

ENTRYPOINT ["docker-qrm-entrypoint.sh"]
CMD ["php-fpm"]
