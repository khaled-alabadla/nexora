# Nexora — developer command surface.
# Every application command runs inside Docker (see ADR-0003).

COMPOSE ?= docker compose
APP     := $(COMPOSE) exec -T app
APP_TTY := $(COMPOSE) exec app
NODE    := $(COMPOSE) exec -T node

.DEFAULT_GOAL := help

# ---------------------------------------------------------------------------
help: ## List available targets
	@grep -E '^[a-zA-Z0-9_.-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}'

# ---------------------------------------------------------------------------
## Environment lifecycle
# ---------------------------------------------------------------------------
build: ## Build the Docker images
	$(COMPOSE) build

up: ## Start the full stack in the background
	$(COMPOSE) up -d

down: ## Stop the stack
	$(COMPOSE) down

restart: ## Recreate the stack
	$(COMPOSE) down && $(COMPOSE) up -d

destroy: ## Stop the stack and delete volumes (DESTROYS local data)
	$(COMPOSE) down -v

ps: ## Show container status
	$(COMPOSE) ps

logs: ## Tail logs for all services
	$(COMPOSE) logs -f --tail=100

shell: ## Open a shell in the app container
	$(APP_TTY) bash

setup: ## First-time setup: deps, app key, migrations
	$(COMPOSE) up -d
	$(APP) composer install
	$(APP) sh -c '[ -f .env ] || cp .env.example .env'
	$(APP) php artisan key:generate
	$(APP) php artisan migrate --force
	$(NODE) npm install
	@echo "\nNexora is up:  http://localhost:$${APP_FORWARD_PORT:-8000}/api/v1/health"

# ---------------------------------------------------------------------------
## Backend — Laravel
# ---------------------------------------------------------------------------
composer: ## Run composer (ARGS="require foo/bar")
	$(APP) composer $(ARGS)

artisan: ## Run artisan (ARGS="migrate:status")
	$(APP) php artisan $(ARGS)

migrate: ## Run database migrations
	$(APP) php artisan migrate

fresh: ## Drop all tables and re-migrate with seeders
	$(APP) php artisan migrate:fresh --seed

module: ## Scaffold a new module (name=Sales)
	$(APP) php artisan make:module $(name)

# ---------------------------------------------------------------------------
## Quality gates — backend
# ---------------------------------------------------------------------------
format: ## Auto-fix code style (Pint)
	$(APP) ./vendor/bin/pint

lint: ## Check code style without fixing (Pint)
	$(APP) ./vendor/bin/pint --test

analyse: ## Static analysis (PHPStan / Larastan)
	$(APP) ./vendor/bin/phpstan analyse --memory-limit=512M

test: ## Run the backend test suite (Pest, MySQL)
	$(APP) php artisan test

test-parallel: ## Run the backend test suite in parallel
	$(APP) php artisan test --parallel

check: lint analyse test ## Run all backend quality gates

# ---------------------------------------------------------------------------
## Quality gates — frontend
# ---------------------------------------------------------------------------
npm: ## Run npm (ARGS="run build")
	$(NODE) npm $(ARGS)

fe-lint: ## Lint the frontend (ESLint)
	$(NODE) npm run lint

fe-typecheck: ## Type-check the frontend (tsc)
	$(NODE) npm run typecheck

fe-test: ## Run frontend unit tests (Vitest)
	$(NODE) npm run test

fe-build: ## Production build of the frontend
	$(NODE) npm run build

check-frontend: fe-lint fe-typecheck fe-test fe-build ## Run all frontend quality gates

# ---------------------------------------------------------------------------
check-all: check check-frontend ## Run every quality gate (backend + frontend)

.PHONY: help build up down restart destroy ps logs shell setup composer artisan \
        migrate fresh module format lint analyse test test-parallel check \
        npm fe-lint fe-typecheck fe-test fe-build check-frontend check-all
