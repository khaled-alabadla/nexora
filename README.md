# Nexora

> Run your business. One platform.

Nexora is a production-grade, multi-tenant Business Management & ERP SaaS platform.
It lets multiple companies manage products, inventory, sales, purchases, accounting,
and reporting from a single application, with strict tenant isolation.

This repository is a **portfolio-quality reference implementation** that demonstrates
real-world engineering practices: a modular monolith, server-side authorization,
auditable inventory, balanced double-entry accounting, transactional financial
operations, and a comprehensive automated test suite.

---

## Architecture at a glance

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 12, Laravel Sanctum |
| Database | MySQL 8.4 |
| Cache / Queue / Rate limiting | Redis 7 |
| Frontend | React 19, TypeScript, Vite, TailwindCSS, shadcn/ui, TanStack Query, Zustand |
| Mail (dev) | Mailpit |
| Local orchestration | Docker Compose |

The backend is a **modular monolith**: business capabilities live in
`backend/modules/<Module>` under the `Modules\` namespace, each with its own
service provider, routes, migrations, and tests. See
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) and
[`docs/adr/`](docs/adr/) for the reasoning.

---

## Source of truth

| Document | Purpose |
|---|---|
| [`CLAUDE.md`](CLAUDE.md) | Engineering workflow & non-negotiable rules |
| [`docs/PRD.md`](docs/PRD.md) | Product requirements |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | System architecture |
| [`docs/ROADMAP.md`](docs/ROADMAP.md) | Phased delivery plan |
| [`docs/DATABASE.md`](docs/DATABASE.md) | Data model |
| [`docs/API.md`](docs/API.md) | REST API conventions |
| [`docs/SECURITY.md`](docs/SECURITY.md) | Security requirements |
| [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md) | Local setup & day-to-day commands |
| [`docs/TESTING.md`](docs/TESTING.md) | Test strategy |
| [`docs/adr/`](docs/adr/) | Architecture Decision Records |

---

## Quick start

> Requires Docker Desktop (Compose v2). No local PHP/Node needed.

```bash
# 1. Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# 2. Build and start the stack
make up            # or: docker compose up -d --build

# 3. Install dependencies, generate app key, migrate
make setup

# 4. Verify
curl http://localhost:8000/api/v1/health
# → {"data":{"status":"ok","database":true,"cache":true},"message":"OK"}
```

| Service | URL |
|---|---|
| API | http://localhost:8000/api/v1 |
| Frontend | http://localhost:5173 |
| Mailpit | http://localhost:8025 |

See [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md) for the full command reference.

---

## Quality gates

Every change must pass, locally and in CI:

```bash
make check          # backend: Pint (format), PHPStan (static analysis), Pest (tests)
make check-frontend # frontend: ESLint, tsc, Vitest, production build
```

Tests run against **MySQL** (not SQLite) — see
[ADR-0005](docs/adr/0005-test-database-mysql.md).

---

## Project status

Delivery is incremental through the phases in [`docs/ROADMAP.md`](docs/ROADMAP.md).
The live status of work in progress is tracked in
[`.wolf/STATUS.md`](.wolf/STATUS.md).

## License

Not yet licensed. All rights reserved by the project owner pending a license decision.
