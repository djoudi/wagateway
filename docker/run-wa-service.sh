#!/bin/sh
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export HOME="${HOME:-/root}"
export PORT="${PORT:-3000}"
export LARAVEL_SECRET="${LARAVEL_SECRET:-${WA_SERVICE_SECRET:-}}"
export LARAVEL_WEBHOOK_URL="${LARAVEL_WEBHOOK_URL:-http://127.0.0.1/internal/wa-events}"
export SESSION_PATH="${SESSION_PATH:-/var/www/html/wa-service/sessions}"
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD="${PUPPETEER_SKIP_CHROMIUM_DOWNLOAD:-true}"
cd /var/www/html/wa-service || exit 1
if [ ! -d node_modules ]; then
    echo "wa-service: node_modules missing" >&2
    exit 1
fi
echo "wa-service: starting node src/index.js PORT=${PORT}"
exec /usr/bin/node /var/www/html/wa-service/src/index.js
