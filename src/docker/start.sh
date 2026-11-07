#!/bin/sh
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "ERREUR : la variable APP_KEY est obligatoire (générer avec : php artisan key:generate --show)." >&2
    exit 1
fi

DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
[ -f "$DB_FILE" ] || touch "$DB_FILE"

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public
chown -R www-data:www-data storage bootstrap/cache "$(dirname "$DB_FILE")"

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force || true
php artisan migrate --force
# UserSeeder est idempotent (comptes Riadh/Ahmed) ; DatabaseSeeder créerait des données de démo à chaque démarrage
php artisan db:seed --class=UserSeeder --force

php-fpm -D
exec nginx -g 'daemon off;'
