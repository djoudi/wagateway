#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────
# Runtime bootstrap — runs every container start as root.
#
# Default (no extra args): start Supervisor (nginx + php-fpm + workers).
# Extra args (compose `command:`): run that command instead, so the same
# image can be php-fpm / horizon / reverb / scheduler on a VPS.
# ─────────────────────────────────────────────────────────────────────────

mkdir -p /var/log/supervisor /var/log/php /var/log/nginx /run/nginx /var/run
chown -R wagateway:wagateway /var/log/supervisor /var/log/php /var/log/nginx /run/nginx /var/run || true

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set. Set APP_KEY (and APP_URL, DB_*, REDIS_*, REVERB_*, WA_SERVICE_SECRET) in the platform environment." >&2
    exit 1
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
