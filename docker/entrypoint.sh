#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────
# Runtime bootstrap — runs every container start as root.
#
# The platform (EasyPanel/Coolify) injects all configuration via process
# environment variables at runtime. Laravel's env() helper reads those
# directly, so no .env file is required — APP_KEY must be provided by the
# platform (it is a required variable).
# ─────────────────────────────────────────────────────────────────────────

# Ensure runtime dirs exist and are writable by the container processes.
mkdir -p /var/log/supervisor /var/log/php /var/log/nginx /run/nginx /var/run
chown -R wagateway:wagateway /var/log/supervisor /var/log/php /var/log/nginx /run/nginx /var/run || true

if ! command -v nginx >/dev/null 2>&1 && [ ! -x /usr/sbin/nginx ]; then
    echo "ERROR: nginx is not installed in this image. Rebuild from the root Dockerfile (all-in-one), not docker/Dockerfile.app." >&2
    exit 1
fi
if ! command -v supervisord >/dev/null 2>&1 && [ ! -x /usr/bin/supervisord ]; then
    echo "ERROR: supervisord is not installed in this image. Rebuild from the root Dockerfile." >&2
    exit 1
fi

nginx -t

# Guard: without an app key Laravel cannot boot. Fail fast with a clear message.
if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set. Set APP_KEY (and APP_URL, DB_*, REDIS_*, REVERB_*, WA_SERVICE_SECRET) in the platform environment." >&2
    exit 1
fi

# Runtime Laravel boot commands — run as wagateway so generated files
# (storage, bootstrap/cache) keep the same owner as the php-fpm workers.
su-exec wagateway sh -c '
    php artisan storage:link --no-interaction || true
    php artisan migrate --force --no-interaction || true
    php artisan config:cache --no-interaction || true
'

# Start all services under supervisord (runs as root; php-fpm/nginx drop
# privileges via their own configs).
exec /usr/bin/supervisord -c /etc/supervisord.conf
