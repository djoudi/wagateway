#!/bin/bash
set -euo pipefail

# ─── Colors ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
ok()   { echo -e "${GREEN}✓ $1${NC}"; }
warn() { echo -e "${YELLOW}⚠ $1${NC}"; }
fail() { echo -e "${RED}✗ $1${NC}"; exit 1; }

echo ""
echo "  ██╗    ██╗ █████╗  ██████╗  █████╗ ████████╗███████╗██╗    ██╗ █████╗ ██╗   ██╗"
echo "  ██║    ██║██╔══██╗██╔════╝ ██╔══██╗╚══██╔══╝██╔════╝██║    ██║██╔══██╗╚██╗ ██╔╝"
echo "  ██║ █╗ ██║███████║██║  ███╗███████║   ██║   █████╗  ██║ █╗ ██║███████║ ╚████╔╝ "
echo "  ██║███╗██║██╔══██║██║   ██║██╔══██║   ██║   ██╔══╝  ██║███╗██║██╔══██║  ╚██╔╝  "
echo "  ╚███╔███╔╝██║  ██║╚██████╔╝██║  ██║   ██║   ███████╗╚███╔███╔╝██║  ██║   ██║   "
echo "   ╚══╝╚══╝ ╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚══════╝ ╚══╝╚══╝ ╚═╝  ╚═╝   ╚═╝   "
echo ""
echo "  Production Deploy Script v1.0"
echo ""

# ─── Pre-flight checks ────────────────────────────────────────────────────────
echo "── Pre-flight checks ──────────────────────────────────────────────────────"

command -v docker >/dev/null 2>&1 || fail "Docker not installed"
ok "Docker available"

command -v docker compose >/dev/null 2>&1 || fail "Docker Compose plugin not installed"
ok "Docker Compose available"

[[ -f ".env" ]] || fail ".env file not found. Copy .env.example and fill in values."
ok ".env file found"

# Validate required env vars
source .env
: "${APP_KEY:?APP_KEY is required. Run: php artisan key:generate}"
: "${DB_PASSWORD:?DB_PASSWORD is required}"
: "${REDIS_PASSWORD:?REDIS_PASSWORD is required}"
: "${WA_SERVICE_SECRET:?WA_SERVICE_SECRET is required (min 32 chars)}"
: "${REVERB_APP_KEY:?REVERB_APP_KEY is required}"
ok "Required env vars present"

# SSL check
if [[ ! -f "docker/ssl/cert.pem" ]] || [[ ! -f "docker/ssl/key.pem" ]]; then
    warn "SSL certificates not found in docker/ssl/"
    warn "Nginx will fail to start. See README for SSL setup."
    read -p "Continue anyway? (y/N) " confirm
    [[ "${confirm,,}" == "y" ]] || exit 1
else
    ok "SSL certificates found"
fi

# ─── Build ─────────────────────────────────────────────────────────────────────
echo ""
echo "── Building containers ────────────────────────────────────────────────────"
docker compose build --no-cache --parallel
ok "Containers built"

# ─── Start infrastructure ─────────────────────────────────────────────────────
echo ""
echo "── Starting infrastructure ────────────────────────────────────────────────"
docker compose up -d postgres redis
echo "  Waiting for PostgreSQL and Redis..."
sleep 10
docker compose exec postgres pg_isready -U "${DB_USERNAME}" -d "${DB_DATABASE}" || fail "PostgreSQL not ready"
ok "PostgreSQL ready"
docker compose exec redis redis-cli -a "${REDIS_PASSWORD}" ping | grep -q PONG || fail "Redis not ready"
ok "Redis ready"

# ─── Database ──────────────────────────────────────────────────────────────────
echo ""
echo "── Database setup ─────────────────────────────────────────────────────────"
docker compose run --rm app php artisan migrate --force --no-interaction
ok "Migrations complete"

docker compose run --rm app php artisan db:seed --class=PlanSeeder --force --no-interaction
ok "Plans seeded"

# ─── Optimize ──────────────────────────────────────────────────────────────────
echo ""
echo "── Optimizing application ─────────────────────────────────────────────────"
docker compose run --rm app php artisan config:cache
docker compose run --rm app php artisan route:cache
docker compose run --rm app php artisan view:cache
docker compose run --rm app php artisan event:cache
ok "Application optimized"

# ─── Start all services ────────────────────────────────────────────────────────
echo ""
echo "── Starting all services ──────────────────────────────────────────────────"
docker compose up -d
sleep 5

echo ""
echo "── Service status ─────────────────────────────────────────────────────────"
docker compose ps

echo ""
echo "── Post-deployment checks ─────────────────────────────────────────────────"

# Health check
sleep 3
HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "http://localhost/api/health" 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" == "200" ]]; then
    ok "API health check passed (HTTP 200)"
else
    warn "API health check returned HTTP $HTTP_CODE — check nginx logs"
fi

# WA Service
WA_CODE=$(curl -sk -o /dev/null -w "%{http_code}" \
    -H "X-WG-Secret: ${WA_SERVICE_SECRET}" \
    "http://localhost:3000/health" 2>/dev/null || echo "000")
if [[ "$WA_CODE" == "200" ]]; then
    ok "WA Service health check passed"
else
    warn "WA Service returned HTTP $WA_CODE"
fi

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  ✓ WaGateway deployed successfully!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "  Dashboard:  https://${APP_URL#https://}"
echo "  Admin:      https://${APP_URL#https://}/admin"
echo "  API Health: https://${APP_URL#https://}/api/health"
echo ""
echo "  Next step: Create your first user:"
echo "  docker exec wg_app php artisan user:generate-api-key YOUR_EMAIL"
echo ""
