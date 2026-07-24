#!/bin/sh
set -e

# DATABASE_URL (Neon) ou variables separees (PostgreSQL Render liee)
if [ -z "$DATABASE_URL" ] && [ -n "$DB_HOST" ]; then
  DB_PORT="${DB_PORT:-5432}"
  export DATABASE_URL="postgresql://${DB_USERNAME}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}?sslmode=require"
fi

if [ -z "$DATABASE_URL" ]; then
  echo "ERREUR: base de donnees non configuree."
  echo ""
  echo "Option A — Neon (gratuit, recommande):"
  echo "  1. https://neon.tech -> New Project -> copier Connection string"
  echo "  2. Render -> Environment -> DATABASE_URL = postgresql://..."
  echo ""
  echo "Option B — PostgreSQL Render existante:"
  echo "  Render -> Web Service -> Environment -> Add Database -> choisir votre PostgreSQL"
  echo ""
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

PORT="${PORT:-8000}"
echo "Demarrage sur le port ${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
