#!/bin/bash
# Generates every secret WaGateway needs, ready to paste into Coolify's
# "Environment Variables" tab (bulk edit → paste as .env format).
#
# Run this LOCALLY (not inside the build) — you need the output before
# your first deploy, since Coolify needs these values to already exist
# when it builds and starts the containers.
#
# Usage: bash generate-secrets.sh

set -euo pipefail

rand_hex() { openssl rand -hex "${1:-32}"; }
rand_b64() { openssl rand -base64 "${1:-32}" | tr -d '\n'; }

echo "# ── Paste this entire block into Coolify → Environment Variables → Bulk Edit ──"
echo ""
echo "APP_KEY=base64:$(rand_b64 32)"
echo "DB_PASSWORD=$(rand_hex 24)"
echo "REDIS_PASSWORD=$(rand_hex 24)"
echo "WA_SERVICE_SECRET=$(rand_hex 32)"
echo "REVERB_APP_ID=wagateway"
echo "REVERB_APP_KEY=$(rand_hex 16)"
echo "REVERB_APP_SECRET=$(rand_hex 32)"
echo ""
echo "# ── Fill these in manually (not auto-generatable) ──"
echo "APP_URL=https://CHANGE-ME.your-domain.com"
echo "VITE_REVERB_HOST=CHANGE-ME.your-domain.com"
echo "DB_DATABASE=wagateway"
echo "DB_USERNAME=wagateway"
echo "ADMIN_EMAILS=your-email@example.com"
echo "CHARGILY_API_KEY="
echo "CHARGILY_WEBHOOK_SECRET="
echo "MAIL_HOST="
echo "MAIL_USERNAME="
echo "MAIL_PASSWORD="
echo ""
echo "# ── Safe to leave as-is (also present in .env.example) ──"
echo "APP_ENV=production"
echo "APP_DEBUG=false"
echo "APP_TIMEZONE=Africa/Algiers"
echo "DB_CONNECTION=pgsql"
echo "DB_HOST=postgres"
echo "DB_PORT=5432"
echo "REDIS_HOST=redis"
echo "REDIS_PORT=6379"
echo "REDIS_DB=0"
echo "REDIS_CACHE_DB=1"
echo "BROADCAST_CONNECTION=reverb"
echo "REVERB_SERVER_HOST=0.0.0.0"
echo "REVERB_SERVER_PORT=8080"
echo "REVERB_HOST=reverb"
echo "REVERB_PORT=8080"
echo "REVERB_SCHEME=http"
echo "VITE_REVERB_APP_KEY=\${REVERB_APP_KEY}"
echo "VITE_REVERB_PORT=443"
echo "VITE_REVERB_SCHEME=https"
echo "QUEUE_CONNECTION=redis"
echo "HORIZON_PREFIX=wg_"
echo "SESSION_DRIVER=redis"
echo "SESSION_LIFETIME=120"
echo "SESSION_ENCRYPT=true"
echo "SESSION_SECURE_COOKIE=true"
echo "CACHE_STORE=redis"
echo "MAIL_MAILER=smtp"
echo "MAIL_PORT=587"
echo "MAIL_ENCRYPTION=tls"
echo "MAIL_FROM_ADDRESS=noreply@your-domain.com"
echo "MAIL_FROM_NAME=WaGateway"
echo "LOG_CHANNEL=daily"
echo "LOG_LEVEL=warning"
echo "CHARGILY_MODE=test"
echo "SUBSCRIPTION_GRACE_DAYS=3"
echo ""
echo "# ── Done. This entire output can be pasted as one block into Coolify. ──"
