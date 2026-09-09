# ADR-0003: Full Docker development environment

- Status: Accepted
- Date: 2026-09-09

## Context

`docs/ARCHITECTURE.md` requires PHP 8.4+ and MySQL 8+. The current development
machine has PHP 8.2 and a MySQL 9.x client. Running quality gates against a
different PHP/MySQL version than production would produce version-skewed results
(different type-juggling, deprecations, SQL modes).

## Decision

All application processes run in Docker via `docker-compose.yml`:

| Service | Image / base | Purpose |
|---|---|---|
| `app` | `php:8.4-fpm` (custom Dockerfile) | Laravel (FPM) |
| `nginx` | `nginx:1.27-alpine` | HTTP front for `app` |
| `queue` | same image as `app` | `php artisan queue:work` |
| `scheduler` | same image as `app` | `php artisan schedule:work` |
| `mysql` | `mysql:8.4` | primary + test databases |
| `redis` | `redis:7-alpine` | cache, queue, rate limiting |
| `node` | `node:22-alpine` | Vite dev server / frontend tooling |
| `mailpit` | `axllent/mailpit` | captured mail in dev |

Host PHP and host Node are **not supported** for running the app or its checks.
All commands are wrapped in a `Makefile` that shells into the containers
(`docker compose exec`).

The `node:22-alpine` service provides CI parity; developers may alternatively run
`npm run dev` on the host for faster HMR on Windows (documented, not required).

## Consequences

- Reproducible environment identical to CI.
- Onboarding is `make up && make setup`.
- Requires Docker Desktop; first build downloads images and compiles PHP
  extensions (slower first run).
- Windows bind-mount file watching for Vite can be slow — hence the documented
  host-`npm` escape hatch.
