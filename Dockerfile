FROM php:8.5.12-fpm AS php

# Installer les dépendances système, Nginx et les extensions pour PostgreSQL (libpq-dev)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY src/ .

COPY docker/nginx/default.conf /etc/nginx/sites-available/default

RUN composer install --no-dev --optimize-autoloader

FROM node:24-alpine AS frontend

WORKDIR /var/www/html

COPY src/package.json ./
RUN npm install --ignore-scripts --no-package-lock

COPY src/vite.config.js ./vite.config.js
COPY src/resources ./resources
COPY src/storage/framework/views ./storage/framework/views
COPY --from=php /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN mkdir -p public && npm run build

FROM php AS production

COPY --from=frontend /var/www/html/public/build ./public/build

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000

# Commande de démarrage : Configuration, migrations sur PostgreSQL, seeders et lancement des services
CMD sed -i "s/_PORT_PLACEHOLDER_/$PORT/g" /etc/nginx/sites-available/default && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan storage:link --force --no-interaction && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    service nginx start && \
    php-fpm