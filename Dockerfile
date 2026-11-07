FROM php:8.5.12-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    sqlite3 \
    libsqlite3-dev

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# On copie tout le contenu du dossier 'src' (là où il y a Laravel) directement à la racine du conteneur (/var/www/html)
COPY src/ .

# Copier la configuration Nginx depuis le dossier docker du dépôt
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Installer les dépendances Composer
RUN composer install --no-dev --optimize-autoloader

# Préparer SQLite et les permissions
RUN mkdir -p /var/www/html/database && \
    touch /var/www/html/database/database.sqlite && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

EXPOSE 10000

CMD sed -i "s/_PORT_PLACEHOLDER_/$PORT/g" /etc/nginx/sites-available/default && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    service nginx start && \
    php-fpm