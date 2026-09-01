#!/bin/sh
set -eu
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export HOME="${HOME:-/home/wagateway}"
export PORT="${PORT:-3000}"
export LARAVEL_SECRET="${LARAVEL_SECRET:-${WA_SERVICE_SECRET:-}}"
export LARAVEL_WEBHOOK_URL="${LARAVEL_WEBHOOK_URL:-http://127.0.0.1/internal/wa-events}"
export SESSION_PATH="${SESSION_PATH:-/var/www/html/wa-service/sessions}"
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD="${PUPPETEER_SKIP_CHROMIUM_DOWNLOAD:-true}"
cd /var/www/html/wa-service
echo "wa-service: node src/index.js (PORT=${PORT})"
exec /usr/bin/node /var/www/html/wa-service/src/index.js
