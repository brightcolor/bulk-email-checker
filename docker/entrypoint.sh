#!/bin/sh
set -e

echo "==> Bulk Email Checker — container startup"

# ── Storage permissions ────────────────────────────────────────────────────────
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs bootstrap/cache
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── Ensure .env exists ─────────────────────────────────────────────────────────
if [ ! -f .env ]; then
    echo "==> Copying .env.example to .env"
    cp .env.example .env
fi

# ── Generate app key if missing ────────────────────────────────────────────────
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=base64:$" .env; then
    echo "==> Generating application key"
    php artisan key:generate --force
fi

# ── Wait for MySQL (if configured) ────────────────────────────────────────────
if [ "$DB_CONNECTION" = "mysql" ] && [ -n "$DB_HOST" ]; then
    echo "==> Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
    RETRIES=30
    until mysql -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do
        RETRIES=$((RETRIES-1))
        if [ $RETRIES -eq 0 ]; then
            echo "ERROR: MySQL not available after 30 retries"
            exit 1
        fi
        echo "  Waiting... ($RETRIES retries left)"
        sleep 2
    done
    echo "==> MySQL is ready"
fi

# ── Run migrations ─────────────────────────────────────────────────────────────
echo "==> Running migrations"
php artisan migrate --force --no-interaction

# ── Cache config/routes for production ────────────────────────────────────────
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "==> Caching config & routes"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "==> Starting supervisord"
exec "$@"
