#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Render Postgres suele exponer DATABASE_URL; Laravel 12 usa DB_URL
if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
  export DB_URL="${DATABASE_URL}"
fi

PORT="${PORT:-10000}"

echo "==> Optimizando Laravel..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "==> Migraciones..."
php artisan migrate --force

echo "==> Sirviendo en 0.0.0.0:${PORT}"
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
