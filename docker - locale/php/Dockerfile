FROM php:8.5.12-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev \
        nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip bcmath exif pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN groupadd -g ${GID} dev 2>/dev/null || true \
    && useradd -u ${UID} -g ${GID} -m dev 2>/dev/null || true \
    && mkdir -p /var/www/html/vendor /var/www/html/storage/framework/views /var/www/html/storage/framework/sessions /var/www/html/storage/framework/cache/data \
    && chown -R ${UID}:${GID} /var/www/html

USER ${UID}:${GID}
WORKDIR /var/www/html
