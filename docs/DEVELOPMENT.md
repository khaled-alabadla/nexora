# Nexora — Development Guide

## Prerequisites

- **Docker Desktop** with Compose v2. Nothing else — no host PHP or Node
  (see [ADR-0003](adr/0003-docker-dev-environment.md)).
- ~4 GB free disk for images and volumes.

## First run

```bash
git clone <repo> nexora && cd nexora

cp .env.example .env                 # compose interpolation (ports, UID)
cp backend/.env.example backend/.env # Laravel config
cp frontend/.env.example frontend/.env

make build      # build the PHP image
make up         # start every service
make setup      # composer install, key:generate, migrate, npm install
```

Verify:

```bash
curl http://localhost:8000/api/v1/health
# {"data":{"status":"ok","database":true,"cache":true},"message":"OK"}
```

| Service | URL / port |
|---|---|
| API (nginx → php-fpm) | http://localhost:8000 |
| SPA (Vite dev server) | http://localhost:5173 |
| Mailpit web UI | http://localhost:8025 |
| MySQL (host) | `127.0.0.1:33061` (user `nexora` / `secret`) |
| Redis (host) | `127.0.0.1:63790` |

Host ports are overridable in the root `.env`.

## Everyday commands

All via the `Makefile` (run `make help` for the full list):

| Command | Effect |
|---|---|
| `make up` / `make down` | start / stop the stack |
| `make shell` | bash in the `app` container |
| `make logs` | tail all service logs |
| `make artisan ARGS="…"` | run artisan |
| `make composer ARGS="…"` | run composer |
| `make migrate` / `make fresh` | migrations |
| `make module name=Sales` | scaffold a new module (ADR-0002) |
| `make format` / `make lint` | Pint (fix / check) |
| `make analyse` | PHPStan / Larastan |
| `make test` | Pest suite (MySQL) |
| `make check` | lint + analyse + test (backend gate) |
| `make check-frontend` | eslint + tsc + vitest + build |
| `make check-all` | everything |

## Backend layout

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/HealthController.php
│   │   ├── Middleware/ForceJsonResponse.php
│   │   └── Responses/ApiResponse.php        # canonical envelopes
│   ├── Console/Commands/MakeModuleCommand.php
│   ├── Providers/AppServiceProvider.php
│   └── Support/Modules/ModuleServiceProvider.php  # base for every module
├── modules/<Name>/                          # business modules (ADR-0002)
│   ├── Providers/<Name>ServiceProvider.php
│   ├── Http/  Models/  Services/
│   ├── Routes/api.php                        # loaded under /api/v1
│   ├── Database/{Migrations,Factories,Seeders}/
│   └── Tests/{Feature,Unit}/
├── config/nexora.php                         # api prefix, money precision
├── routes/api.php                            # core routes (health)
├── phpstan.neon  pint.json  phpunit.xml
```

Module providers are registered explicitly in `backend/bootstrap/providers.php`.

## Adding a module

```bash
make module name=Inventory
make composer ARGS="dump-autoload"
```

This scaffolds `backend/modules/Inventory/` and registers
`Modules\Inventory\Providers\InventoryServiceProvider` in
`bootstrap/providers.php`.

## Authentication (from Phase 1)

The SPA uses Sanctum cookie sessions (see
[ADR-0004](adr/0004-authentication-transport.md)). The frontend HTTP client
must:

1. `GET /sanctum/csrf-cookie` before the first mutating request;
2. send `withCredentials` on every request;
3. treat `419` as "refresh CSRF cookie and retry", `401` as "log in".

`SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, and `CORS_ALLOWED_ORIGINS` in
`backend/.env` must list the frontend origin in every environment.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `make setup` fails on DB connection | `make logs` — wait for `mysql` healthy, retry |
| Port already in use | change the `*_FORWARD_PORT` in root `.env`, `make restart` |
| Vite not reloading on Windows | run `npm run dev` on the host instead of the `node` service |
| Stale config after `.env` edit | `make artisan ARGS="config:clear"` |
