#!/bin/sh
set -e

# PostgreSQL via DATABASE_URL (Neon / Render) ou SQLite automatique (demo)
if [ -n "$DATABASE_URL" ]; then
  export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
  echo "Base PostgreSQL detectee (DATABASE_URL)."
elif [ -n "$DB_HOST" ]; then
  DB_PORT="${DB_PORT:-5432}"
  export DB_CONNECTION=pgsql
  export DATABASE_URL="postgresql://${DB_USERNAME}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}?sslmode=require"
  echo "Base PostgreSQL detectee (variables Render)."
else
  export DB_CONNECTION=sqlite
  export DB_DATABASE="${DB_DATABASE:-/app/storage/database/render.sqlite}"
  mkdir -p /app/storage/database
  touch "$DB_DATABASE"
  echo "Mode demo SQLite: ${DB_DATABASE}"
fi

php artisan storage:link 2>/dev/null || true
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

if [ "$SEED_DEMO" = "true" ]; then
  php artisan db:seed --force
fi

PORT="${PORT:-8000}"
echo "Demarrage sur le port ${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
