#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────
# Runtime bootstrap — runs every container start as root.
#
# Default (no extra args): start Supervisor (nginx + php-fpm + workers).
# Extra args (compose `command:`): run that command instead, so the same
# image can be php-fpm / horizon / reverb / scheduler on a VPS.
# ─────────────────────────────────────────────────────────────────────────

export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export HOME="${HOME:-/root}"
export LARAVEL_SECRET="${LARAVEL_SECRET:-${WA_SERVICE_SECRET:-}}"
export PORT="${PORT:-3000}"
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD="${PUPPETEER_SKIP_CHROMIUM_DOWNLOAD:-true}"
export LARAVEL_WEBHOOK_URL="${LARAVEL_WEBHOOK_URL:-http://127.0.0.1/internal/wa-events}"
export SESSION_PATH="${SESSION_PATH:-/var/www/html/wa-service/sessions}"

if [ -z "${PUPPETEER_EXECUTABLE_PATH:-}" ]; then
    for candidate in /usr/bin/chromium /usr/bin/chromium-browser /usr/lib/chromium/chromium; do
        if [ -x "$candidate" ]; then
            export PUPPETEER_EXECUTABLE_PATH="$candidate"
            break
        fi
    done
fi

mkdir -p /var/log/supervisor /var/log/php /var/log/nginx /run/nginx /var/run \
         /home/wagateway /var/www/html/wa-service/src/logs /var/www/html/wa-service/sessions \
         /var/www/html/storage/logs /var/www/html/bootstrap/cache
chown -R wagateway:wagateway /var/log/supervisor /var/log/php /var/log/nginx /run/nginx /var/run \
         /home/wagateway /var/www/html/wa-service/src/logs /var/www/html/wa-service/sessions \
         /var/www/html/storage /var/www/html/bootstrap/cache || true

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set. Set APP_KEY (and APP_URL, DB_*, REDIS_*, REVERB_*, WA_SERVICE_SECRET) in the platform environment." >&2
    exit 1
fi

wait_tcp() {
    host="$1"
    port="$2"
    name="$3"
    i=0
    while [ "$i" -lt 30 ]; do
        if php -r "\$s=@fsockopen('$host', $port, \$e, \$t, 1); if (\$s) { fclose(\$s); exit(0);} exit(1);"; then
            echo "$name is reachable at $host:$port"
            return 0
        fi
        i=$((i + 1))
        echo "waiting for $name ($host:$port) ... $i/30"
        sleep 2
    done
    echo "WARNING: $name not reachable at $host:$port after 60s" >&2
    return 0
}

if [ -n "${DB_HOST:-}" ]; then
    wait_tcp "${DB_HOST}" "${DB_PORT:-5432}" "postgres"
fi
if [ -n "${REDIS_HOST:-}" ]; then
    wait_tcp "${REDIS_HOST}" "${REDIS_PORT:-6379}" "redis"
fi

if command -v su-exec >/dev/null 2>&1; then
    su-exec wagateway sh -c '
        php artisan storage:link --no-interaction || true
        php artisan migrate --force --no-interaction || true
        php artisan config:cache --no-interaction || true
    ' || true
fi

if [ "$#" -gt 0 ]; then
    case "$1" in
        php-fpm|*/php-fpm)
            exec "$@"
            ;;
        *)
            if command -v su-exec >/dev/null 2>&1 && id wagateway >/dev/null 2>&1; then
                exec su-exec wagateway "$@"
            fi
            exec "$@"
            ;;
    esac
fi

if [ ! -x /usr/sbin/nginx ] && ! command -v nginx >/dev/null 2>&1; then
    echo "ERROR: nginx is not installed. This image was built without the all-in-one Dockerfile." >&2
    exit 1
fi
if [ ! -x /usr/bin/supervisord ] && ! command -v supervisord >/dev/null 2>&1; then
    echo "ERROR: supervisord is not installed." >&2
    exit 1
fi

nginx -t
exec /usr/bin/supervisord -c /etc/supervisord.conf
