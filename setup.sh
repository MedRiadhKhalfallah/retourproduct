#!/usr/bin/env bash
set -euo pipefail

export UID GID=$(id -g)
docker compose up -d --build

# Génère le squelette Laravel sans écraser nos fichiers (src/)
if [ ! -f src/artisan ]; then
  docker compose exec app bash -c '
    composer create-project laravel/laravel /tmp/skel --no-interaction &&
    cp -rn /tmp/skel/. /var/www/html/ && rm -rf /tmp/skel'
fi

cp -n .env.example src/.env || true

docker compose exec app composer install --no-interaction
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link || true
docker compose exec app php artisan migrate:fresh --seed --force

echo "App: http://localhost:8456  |  phpMyAdmin: http://localhost:8081"
