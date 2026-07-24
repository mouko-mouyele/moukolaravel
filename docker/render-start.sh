#!/bin/sh
set -e

if [ -z "$DATABASE_URL" ]; then
  echo "ERREUR: DATABASE_URL manquant."
  echo "Render -> Environment -> DATABASE_URL = Internal Database URL (PostgreSQL existante)"
  echo "Ou base gratuite Neon: https://neon.tech"
  exit 1
fi

php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

if [ "$SEED_DEMO" = "true" ]; then
  php artisan db:seed --force
fi

exec /entrypoint supervisord
