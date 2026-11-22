#!/bin/sh
set -eu
cd /var/www/html
PORT="${PORT:-10000}"
case "$PORT" in ''|*[!0-9]*) echo "Invalid service port" >&2; exit 1;; esac
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-}}"
if [ -z "${APP_KEY:-}" ]; then
    : "${APP_KEY_SEED:?Set the application key seed in Render}"
    APP_KEY="base64:$(php -r 'echo base64_encode(hash("sha256", getenv("APP_KEY_SEED"), true));')"
    export APP_KEY
fi
: "${DB_URL:?Configure the separate presentation database connection}"
sed -i "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf
php artisan config:clear
php artisan migrate --force
if [ "${PRESENTATION_MODE:-false}" = "true" ]; then
    php artisan presentation:initialize
fi
php artisan storage:link --force
chown -R www-data:www-data storage bootstrap/cache public/uploads
php artisan config:cache
php artisan view:cache
exec apache2-foreground
