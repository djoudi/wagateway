#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────
# Runtime bootstrap — runs every container start.
#
# The platform (EasyPanel/Coolify) injects all configuration via process
# environment variables at runtime. Laravel's env() helper reads those
# directly, so no .env file is required — APP_KEY must be provided by the
# platform (it is a required variable).
# ─────────────────────────────────────────────────────────────────────────

# Ensure runtime dirs exist and are writable by the container user.
mkdir -p /var/log/supervisor /var/log/php /var/log/nginx /run/nginx /var/run
chown -R wagateway:wagateway /var/log/supervisor /var/log/php /var/log/nginx /run/nginx /var/run || true

# Guard: without an app key Laravel cannot boot. Fail fast with a clear message.
if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set. Set APP_KEY (and APP_URL, DB_*, REDIS_*, REVERB_*, WA_SERVICE_SECRET) in the platform environment." >&2
    exit 1
fi

# Runtime Laravel boot commands.
php artisan storage:link --no-interaction || true
php artisan migrate --force --no-interaction

# Start all services under supervisord.
exec /usr/bin/supervisord -c /etc/supervisord.conf
