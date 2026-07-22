#!/bin/bash
set -euo pipefail

echo "── WaGateway Update ───────────────────────────────────────────────────────"

# Enable maintenance mode
docker exec wg_app php artisan down --retry=60 --render="errors::503"
echo "✓ Maintenance mode ON"

# Pull latest changes
git pull origin main

# Rebuild app container
docker compose build --no-cache app horizon reverb scheduler

# Run migrations
docker compose run --rm app php artisan migrate --force --no-interaction
echo "✓ Migrations done"

# Clear and re-cache
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan optimize
echo "✓ Cache refreshed"

# Restart workers
docker compose restart app horizon reverb scheduler
echo "✓ Services restarted"

# Disable maintenance mode
docker exec wg_app php artisan up
echo "✓ Maintenance mode OFF"

echo ""
echo "✓ Update complete!"
