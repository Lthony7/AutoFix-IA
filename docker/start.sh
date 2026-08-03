#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Render Postgres: DATABASE_URL → Laravel DB_URL
if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
  export DB_URL="${DATABASE_URL}"
fi

# Si hay URL, no dejes que DB_HOST=127.0.0.1 (defaults) gane la conexión
if [ -n "${DB_URL:-}" ]; then
  unset DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD || true
  export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
fi

PORT="${PORT:-10000}"

if [ -z "${DB_URL:-}" ] && { [ -z "${DB_HOST:-}" ] || [ "${DB_HOST}" = "127.0.0.1" ] || [ "${DB_HOST}" = "localhost" ]; }; then
  echo "ERROR: No hay Postgres configurado."
  echo "En Render → Environment agrega DATABASE_URL (Internal Database URL de tu Postgres)"
  echo "o DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD."
  exit 1
fi

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
