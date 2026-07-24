#!/bin/sh
set -e

# APP_KEY obligatoire — generer si absent (Render parfois ne le injecte pas a temps)
if [ -z "$APP_KEY" ]; then
  export APP_KEY=$(php artisan key:generate --show --no-interaction)
  echo "APP_KEY genere automatiquement."
fi

# PostgreSQL ou SQLite demo
if [ -n "$DATABASE_URL" ]; then
  export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
  echo "Base PostgreSQL (DATABASE_URL)."
elif [ -n "$DB_HOST" ]; then
  DB_PORT="${DB_PORT:-5432}"
  export DB_CONNECTION=pgsql
  export DATABASE_URL="postgresql://${DB_USERNAME}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}?sslmode=require"
  echo "Base PostgreSQL (Render)."
else
  export DB_CONNECTION=sqlite
  export DB_DATABASE="/app/storage/database/render.sqlite"
  export SESSION_DRIVER=array
  unset DATABASE_URL
  mkdir -p /app/storage/database
  touch "$DB_DATABASE"
  chmod 664 "$DB_DATABASE"
  echo "Mode demo SQLite."
fi

# Permissions ecriture (logs, cache, sessions, sqlite)
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

php artisan storage:link 2>/dev/null || true
php artisan config:clear
php artisan migrate --force

if [ "$SEED_DEMO" = "true" ]; then
  php artisan db:seed --force
fi

PORT="${PORT:-8000}"
echo "Demarrage sur le port ${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
