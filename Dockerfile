FROM php:8.3-fpm-alpine

LABEL maintainer="qrm.sg"

# System packages + PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    redis \
    nodejs \
    npm \
    postgresql-dev \
    postgresql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-libs \
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
       opcache \
       pcntl \
       mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS linux-headers \
    && mkdir -p /var/www/qrm.sg \
    && mkdir -p /run/nginx \
    && mkdir -p /var/log/supervisor

# Composer
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

WORKDIR /var/www/qrm.sg

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
