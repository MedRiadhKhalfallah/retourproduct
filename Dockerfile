FROM php:8.5.12-fpm

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