---
description: session handoff, regenerate with /handoff when a quest finishes
budget_tokens: 1000
---
# STATUS — Nexora

> Single source of truth for resuming work. Read this FIRST when starting a session.
> Last updated: 2026-09-09

---

## Current state

**Phase 0 — Foundation.** Autonomous build mode (user prompt: full autonomy through all phases).
Branch model: `main` / `develop` / `feature/*`, local `--no-ff` merges.
**`git push` is BLOCKED** by the auto-mode classifier — all work is local. `.github/workflows/ci.yml`
will run once someone pushes. Local full quality suite is the gate.

### Done (merged to `develop`)
- **0.1** repo skeleton: gitignore/attributes/editorconfig, README, ADR 0001–0005. (`main`, `eabbeda`)
- **0.2** Docker env: `docker-compose.yml` (8 services), PHP 8.4 Dockerfile, nginx/mysql-init,
  `Makefile`, `.env.example`. `docker compose config` valid. (`c5a3ea9`)
- **0.3–0.8** backend foundation (merged): Laravel 12.69, module system (ModuleServiceProvider +
  `make:module` + 13 modules), thin API (ApiResponse envelope, HealthController, ForceJsonResponse,
  JSON exceptions), Sanctum SPA config + cors.php, Pest/PHPStan L6/Pint gates.
  **Verified in-container:** pint PASS · phpstan L6 PASS · pest 12/12 PASS ·
  `curl :8000/api/v1/health` → `{"data":{"status":"ok","database":true,"cache":true},"message":"OK"}`.

### In progress
- **0.9–0.11** frontend (`feature/0.9-react-frontend`): Vite + React 19 + TS strict scaffolded;
  ESLint flat + Prettier, Tailwind v4, TanStack Query, Zustand, Vitest + Testing Library.
  Source written (api client, health feature, store, tests). **Blocked on slow `npm install`.**

### Next
- 0.9 verify: `npm run lint && typecheck && test && build` green in `node` container.
- **0.12** `feature/0.12-ci-pipeline`: `.github/workflows/ci.yml` (backend + frontend jobs,
  mysql+redis services), gitleaks.
- **0.13** `feature/0.13-phase0-docs`: fix API.md (auth+envelope), ARCHITECTURE.md (PHP8.4/modules),
  ROADMAP.md (phpstan/coverage). Update DATABASE/SECURITY as needed.
- Phase 0 acceptance checklist → CODE-REVIEW → FIX → merge `develop`→`phase/0-foundation`→`main`.
- Then Phase 1 (Identity & Multi-Tenancy).

### Environment deviations (documented)
- **mysql:8.0** not 8.4 — 8.4 image would not pull reliably. Overridable via `MYSQL_IMAGE`.
  ADR-0005 updated. Satisfies DATABASE.md "MySQL 8+".
- `backend-vendor` named volume added to compose (Windows bind-mount perf: dump-autoload was 150s).
- Node local is v25; containers/CI pin Node 22 (`.nvmrc`).

### Closed decisions (2026-09-09)
- Modules: hand-rolled PSR-4 `Modules\`, explicit providers. No nwidart.
- Auth: Sanctum SPA cookie session. Personal-access tokens later for external clients.
- Dev env: full Docker; host PHP unsupported.
- Test DB: MySQL (`nexora_test`), not SQLite.
- PHPStan L6 now → L8 Phase 1 exit. Coverage floor 60%.

### Open decisions (deferred; not blocking Phase 0)
- RBAC: `company_user.role_id` = one role per user per company — confirm Phase 1.
- Money DECIMAL precision — `config/nexora.php` has 18,2 / 18,4 draft; ratify Phase 1.
- `inventory_movements.unit_cost` missing (COGS) — Phase 2/5.
- Soft-delete policy per entity — later phases.

### Scope guard
Phase 0 ships envelope + health check ONLY. Tenant scoping, auth middleware, policies,
`BelongsToCompany` = Phase 1.

---

## Active architecture

- **Stack:** Laravel 12 / PHP 8.4 / MySQL 8.0 / Redis 7 / Sanctum;
  React 19 + Vite + TS + Tailwind v4 + shadcn/ui + TanStack Query + Zustand. Modular monolith.
- **Layout:** `backend/` (`app/`, `modules/<Name>/`), `frontend/`, `docker/`, `docs/` (+ `adr/`),
  `.github/workflows/`, `docker-compose.yml`, `Makefile`.
- **13 modules** (empty): Identity, Companies, Customers, Suppliers, Products, Inventory, Sales,
  Purchases, Accounting, Expenses, Reports, Notifications, Audit. Registered in
  `backend/bootstrap/providers.php`.
- **Patterns:** Controllers thin → Application Services → Domain → Eloquent. `/api/v1` prefix.
  Success `{data,message}`; errors `{message,errors}`. Financial + inventory ops transactional.
  Never trust client company_id.

---

## Useful commands

```bash
make up / make down / make setup        # stack lifecycle
make check                              # backend gate: pint + phpstan + pest
make check-frontend                     # eslint + tsc + vitest + build
docker compose exec app php artisan …   # or: make artisan ARGS="…"
curl http://localhost:8000/api/v1/health
```

Backend host ports: API :8000, MySQL :33061, Redis :63790, Mailpit :8025, Vite :5173.

---

## References

- `.wolf/cerebrum.md` — decisions + do-not-repeat
- `docs/adr/` — ADR 0001–0005
- `docs/DEVELOPMENT.md`, `docs/TESTING.md`
