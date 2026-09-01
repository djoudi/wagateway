.PHONY: deploy update logs shell db test horizon restart ssl-setup

COMPOSE_VPS := docker compose -f docker-compose.vps.yml

## Deploy the application for the first time
deploy:
	@bash deploy.sh

## Zero-downtime update
update:
	@bash update.sh

## Tail all logs
logs:
	@$(COMPOSE_VPS) logs -f --tail=50

## Open Laravel shell (tinker)
shell:
	@docker exec -it wg_app php artisan tinker

## Open database shell
db:
	@docker exec -it wg_postgres psql -U $$(grep DB_USERNAME .env | cut -d= -f2) -d $$(grep DB_DATABASE .env | cut -d= -f2)

## Run tests
test:
	@$(COMPOSE_VPS) run --rm -e APP_ENV=testing app php artisan test

## Open Horizon dashboard (shows in browser)
horizon:
	@echo "Horizon: https://your-domain.com/horizon"

## Restart all services
restart:
	@$(COMPOSE_VPS) restart

## Generate SSL with certbot (run after DNS is pointed to server)
ssl-setup:
	@read -p "Domain name: " domain; \
	sudo certbot certonly --standalone -d $$domain && \
	sudo cp /etc/letsencrypt/live/$$domain/fullchain.pem docker/ssl/cert.pem && \
	sudo cp /etc/letsencrypt/live/$$domain/privkey.pem docker/ssl/key.pem && \
	sudo chmod 644 docker/ssl/*.pem && \
	echo "SSL certificates installed in docker/ssl/"

## Generate APP_KEY
key:
	@docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

## View WA Service health
wa-health:
	@curl -s -H "X-WG-Secret: $$(grep WA_SERVICE_SECRET .env | cut -d= -f2)" \
	  http://localhost:3000/health | python3 -m json.tool

## Generate API key for a user
api-key:
	@read -p "Email: " email; docker exec wg_app php artisan user:generate-api-key $$email

## Backup database
backup:
	@docker exec wg_postgres pg_dump \
	  -U $$(grep DB_USERNAME .env | cut -d= -f2) \
	  $$(grep DB_DATABASE .env | cut -d= -f2) \
	  > backup_$$(date +%Y%m%d_%H%M%S).sql && echo "Backup saved"

## Show resource usage
stats:
	@docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}"
