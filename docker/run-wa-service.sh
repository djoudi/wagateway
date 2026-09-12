#!/bin/sh
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export HOME="${HOME:-/root}"
# EasyPanel/Coolify/Render set PORT to the public HTTP port (80). Do not
# inherit it — Node would collide with nginx and never bind :3000.
case "${WA_SERVICE_PORT:-}" in
    ''|80|443|8080) WA_SERVICE_PORT=3000 ;;
esac
export WA_SERVICE_PORT
export PORT="${WA_SERVICE_PORT}"
export LARAVEL_SECRET="${LARAVEL_SECRET:-${WA_SERVICE_SECRET:-}}"
export LARAVEL_WEBHOOK_URL="${LARAVEL_WEBHOOK_URL:-http://127.0.0.1/internal/wa-events}"
export SESSION_PATH="${SESSION_PATH:-/var/www/html/wa-service/sessions}"
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD="${PUPPETEER_SKIP_CHROMIUM_DOWNLOAD:-true}"
if [ -z "${PUPPETEER_EXECUTABLE_PATH:-}" ]; then
    for candidate in /usr/bin/chromium /usr/bin/chromium-browser /usr/lib/chromium/chromium; do
        if [ -x "$candidate" ]; then
            export PUPPETEER_EXECUTABLE_PATH="$candidate"
            break
        fi
    done
fi
export PUPPETEER_EXECUTABLE_PATH="${PUPPETEER_EXECUTABLE_PATH:-/usr/bin/chromium}"
cd /var/www/html/wa-service || exit 1
if [ ! -d node_modules ]; then
    echo "wa-service: node_modules missing" >&2
    exit 1
fi
if ! command -v node >/dev/null 2>&1 && [ ! -x /usr/bin/node ]; then
    echo "wa-service: node binary missing" >&2
    exit 1
fi
echo "wa-service: starting node src/index.js WA_SERVICE_PORT=${WA_SERVICE_PORT}"
exec /usr/bin/node /var/www/html/wa-service/src/index.js
