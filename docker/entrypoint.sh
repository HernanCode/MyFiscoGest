#!/bin/sh
set -e

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: falta APP_KEY. Genera una con: php artisan key:generate --show" >&2
    exit 1
fi

# Directorios necesarios (por si el volumen viene vacío)
mkdir -p \
    storage/app \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Base de datos SQLite dentro del volumen
DB_FILE="${DB_DATABASE:-storage/app/database.sqlite}"
case "$DB_FILE" in
    /*) : ;;
    *) DB_FILE="/var/www/html/$DB_FILE" ;;
esac
mkdir -p "$(dirname "$DB_FILE")"
[ -f "$DB_FILE" ] || touch "$DB_FILE"

# Prepara la app (idempotente en cada arranque)
php artisan config:clear >/dev/null 2>&1 || true
php artisan package:discover --ansi
php artisan migrate --force
php artisan db:seed --force

# Cachés de producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permisos para que Apache (www-data) pueda escribir
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
