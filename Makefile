.PHONY: help \
	infra-up infra-down \
	init-local init-staging init-prod \
	local-up local-down local-logs local-key local-shell local-migrate \
	staging-up staging-down staging-logs staging-key staging-shell staging-migrate \
	prod-up prod-down prod-logs prod-key prod-shell prod-migrate \
	proxy-net proxy-up proxy-down proxy-logs proxy-reload \
	test test-coverage pint build-local

COMPOSE_LOCAL := docker compose -f docker-compose.base.yml -f docker-compose.local.yml
COMPOSE_STAGING := docker compose -f docker-compose.base.yml -f docker-compose.staging.yml
COMPOSE_PROD := docker compose -f docker-compose.base.yml -f docker-compose.prod.yml
COMPOSE_PROXY := docker compose -f deploy/proxy/docker-compose.yml

help:
	@echo "Guard Reviews — Docker"
	@echo ""
	@echo "  Локальная разработка"
	@echo "    make infra-up         PostgreSQL + Redis на хосте (backend/frontend на хосте)"
	@echo "    make init-local       Создать backend/.env и .env для local"
	@echo "    make local-up         Полный стек локально"
	@echo "    make local-shell      Shell в контейнере app"
	@echo ""
	@echo "  Сервер: TLS reverse-proxy (один на VDS, поднимается один раз)"
	@echo "    make proxy-net        Создать external network guard-edge"
	@echo "    make proxy-up         Caddy: TLS + маршрутизация prod/staging"
	@echo "    make proxy-logs       Логи прокси"
	@echo ""
	@echo "  Сервер: staging"
	@echo "    make init-staging     Создать backend/.env и .env для staging"
	@echo "    make staging-up       Поднять staging-стек"
	@echo "    make staging-key      php artisan key:generate"
	@echo "    make staging-migrate  Прогнать миграции вручную"
	@echo ""
	@echo "  Сервер: production"
	@echo "    make init-prod        Создать backend/.env и .env для production"
	@echo "    make prod-up          Поднять prod-стек"
	@echo "    make prod-key         php artisan key:generate"
	@echo "    make prod-migrate     Прогнать миграции вручную"
	@echo ""
	@echo "  Тесты / линтер"
	@echo "    make test             pest"
	@echo "    make test-coverage    покрытие кода backend (MIN=80 — порог)"
	@echo "    make pint             laravel/pint"

# -- init --
init-local:
	@bash scripts/init-env.sh local

init-staging:
	@bash scripts/init-env.sh staging

init-prod:
	@bash scripts/init-env.sh production

# -- только Postgres + Redis (для разработки на хосте) --
infra-up:
	docker compose up -d

infra-down:
	docker compose down

# -- LOCAL (полный стек) --
local-up: init-local
	$(COMPOSE_LOCAL) up -d --build
	@echo "✓ http://localhost:$${HTTP_PORT:-8080}/s/{place_uuid}?tenant=demo"

local-down:
	$(COMPOSE_LOCAL) down

local-logs:
	$(COMPOSE_LOCAL) logs -f app worker nginx

local-key:
	$(COMPOSE_LOCAL) exec app php artisan key:generate --force

local-shell:
	$(COMPOSE_LOCAL) exec app sh

local-migrate:
	$(COMPOSE_LOCAL) exec app php artisan migrate --force

# -- STAGING --
staging-up:
	$(COMPOSE_STAGING) up -d --build

staging-down:
	$(COMPOSE_STAGING) down

staging-logs:
	$(COMPOSE_STAGING) logs -f app worker nginx

staging-key:
	$(COMPOSE_STAGING) exec app php artisan key:generate --force

staging-shell:
	$(COMPOSE_STAGING) exec app sh

staging-migrate:
	$(COMPOSE_STAGING) exec app php artisan migrate --force

# -- PRODUCTION --
prod-up:
	$(COMPOSE_PROD) up -d --build

prod-down:
	$(COMPOSE_PROD) down

prod-logs:
	$(COMPOSE_PROD) logs -f app worker nginx

prod-key:
	$(COMPOSE_PROD) exec app php artisan key:generate --force

prod-shell:
	$(COMPOSE_PROD) exec app sh

prod-migrate:
	$(COMPOSE_PROD) exec app php artisan migrate --force

# -- REVERSE PROXY (TLS) --
proxy-net:
	docker network inspect guard-edge >/dev/null 2>&1 || docker network create guard-edge

proxy-up: proxy-net
	$(COMPOSE_PROXY) up -d

proxy-down:
	$(COMPOSE_PROXY) down

proxy-logs:
	$(COMPOSE_PROXY) logs -f

proxy-reload:
	$(COMPOSE_PROXY) exec caddy caddy reload --config /etc/caddy/Caddyfile

# -- разное --
build-local:
	$(COMPOSE_LOCAL) build

test:
	cd backend && php artisan test

test-coverage:
	@bash scripts/coverage.sh \
		$(if $(MIN),--min $(MIN),) \
		$(if $(HTML),--html $(HTML),) \
		$(if $(DOCKER),--docker,)

pint:
	cd backend && ./vendor/bin/pint
