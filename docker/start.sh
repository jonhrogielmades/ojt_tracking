#!/usr/bin/env bash

set -Eeuo pipefail

cd /var/www/html

PORT="${PORT:-10000}"

if [[ ! "$PORT" =~ ^[0-9]+$ ]] || (( PORT < 1 || PORT > 65535 )); then
    echo "PORT must be a number between 1 and 65535." >&2
    exit 1
fi

if [[ -z "${APP_KEY:-}" || "$APP_KEY" == *REPLACE_WITH* ]]; then
    echo "APP_KEY must contain a real Laravel application key." >&2
    exit 1
fi

if ! php -r '
    $key = getenv("APP_KEY");

    if (str_starts_with($key, "base64:")) {
        $key = base64_decode(substr($key, 7), true);
    }

    exit(is_string($key) && strlen($key) === 32 ? 0 : 1);
'; then
    echo "APP_KEY must decode to exactly 32 bytes for AES-256-CBC." >&2
    exit 1
fi

if [[ -z "${DB_URL:-}" ]] && {
    [[ -z "${DB_HOST:-}" ]] \
        || [[ -z "${DB_DATABASE:-}" ]] \
        || [[ -z "${DB_USERNAME:-}" ]]
}; then
    echo "Set DB_URL or the complete DB_HOST, DB_DATABASE, and DB_USERNAME configuration." >&2
    exit 1
fi

if [[ -n "${RENDER_CRON_SECRET:-}" ]] && {
    [[ "$RENDER_CRON_SECRET" == *REPLACE_WITH* ]] \
        || (( ${#RENDER_CRON_SECRET} < 32 ))
}; then
    echo "RENDER_CRON_SECRET must contain at least 32 non-placeholder characters." >&2
    exit 1
fi

export PORT
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:${PORT}}}"
export ASSET_URL="${ASSET_URL:-$APP_URL}"

sed "s/{{PORT}}/${PORT}/g" \
    /etc/nginx/http.d/default.conf.template \
    > /etc/nginx/http.d/default.conf

install -d -m 0775 -o www-data -g www-data \
    bootstrap/cache \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

echo "Preparing Laravel for ${APP_URL}..."
php artisan package:discover --ansi
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi

for attempt in 1 2 3 4 5; do
    if php artisan migrate --force --no-interaction; then
        break
    fi

    if [[ "$attempt" -eq 5 ]]; then
        echo "Database migrations failed after ${attempt} attempts." >&2
        exit 1
    fi

    echo "Database is not ready; retrying migrations in $((attempt * 3)) seconds..." >&2
    sleep $((attempt * 3))
done

php artisan admin:bootstrap --no-interaction

chown -R www-data:www-data bootstrap/cache storage

exec /usr/bin/supervisord -n -c /etc/supervisord.conf
