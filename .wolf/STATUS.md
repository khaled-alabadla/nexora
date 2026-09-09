---
description: session handoff, regenerate with /handoff when a quest finishes
budget_tokens: 1000
---
# STATUS — Nexora

> Read FIRST when resuming. Last updated: 2026-09-09

## Mode

Autonomous build (user: full autonomy through all phases). Branch model
`main` / `develop` / `feature/*`, local `--no-ff` merges, phase → tag.
**`git push` is BLOCKED** (auto-mode classifier; no `gh`/token) — history is
local only. CI (`.github/workflows/ci.yml`) runs on first push; the local full
quality suite is the working gate.

## ✅ Phase 0 — Foundation — COMPLETE (tag `phase-0`, on `main` + `develop`)

Containerized modular monolith. Backend (Laravel 12 / PHP 8.4): `Modules\`
PSR-4 + `make:module`, 13 modules, thin API (`ApiResponse`, JSON exceptions),
`GET /api/v1/health`, Sanctum SPA cookie config, CORS allow-list. Frontend
(React 19 / Vite / TS strict): Tailwind v4, shadcn/ui, TanStack Query, Zustand,
typed API client, health widget. Compose: app/nginx/queue/scheduler/mysql/
redis/node/mailpit. Gates: Pint + Larastan L6 + Pest-on-MySQL (17 tests, 89%);
ESLint + tsc + Prettier + Vitest (11). CI + gitleaks + audits authored.
Docs: ADR 0001–0005, DEVELOPMENT, TESTING, PHASE-0; source docs updated.
Code review: 0 critical / 0 high.

### Deviations (documented — see docs/PHASE-0.md)
- `mysql:8.0` not 8.4 (registry pull unreliable); `MYSQL_IMAGE` override.
- ESLint 10, Node pinned 22 (`.nvmrc`), `backend-vendor` named volume.
- 5 feature branches instead of 14 (no business logic this phase).

## 🚀 Phase 1 — Identity & Multi-Tenancy — NEXT (not started)

Scope (ROADMAP): registration, login, logout, password reset, email
verification; companies, memberships, active company; roles, permissions,
authorization; **tenant isolation** (mandatory, explicitly tested).

Needs: PLAN → GRILL-ME (mandatory, security-critical) → implement in
`Identity` + `Companies` modules.

### Decisions to make in GRILL-ME
- Active company: `users.current_company_id` FK vs session value (lean FK).
- Roles: system-defined global vs per-company custom (Phase 1 = system roles).
- Registration flow: does it create a company + Owner membership (txn)? (yes).
- Tenant scope mechanism: `BelongsToCompany` trait + global scope + a
  `SetActiveCompany` middleware binding company context.
- Money DECIMAL precision — ratify 18,2 / 18,4 (from config/nexora.php).
- `company_user.role_id` = one role per user per company — confirm.
- PHPStan L6 → **L8 is the Phase 1 exit gate**.

## Active architecture

Laravel 12 / PHP 8.4 / MySQL 8.0 / Redis 7 / Sanctum (SPA cookie).
React 19 / Vite / TS / Tailwind v4 / shadcn/ui / TanStack Query / Zustand.
`backend/` (`app/`, `modules/<Name>/`), `frontend/`, `docker/`, `docs/`.
`/api/v1` prefix. Success `{data,message}`; errors `{message,errors}`.
Modules registered in `backend/bootstrap/providers.php`.

## Useful commands

```bash
make up / make setup                 # stack up + install/key/migrate
make check         # backend gate: pint + phpstan + pest
make check-frontend                  # eslint + tsc + prettier + vitest + build
docker compose exec app php artisan …
curl http://localhost:8000/api/v1/health
```
Host ports: API 8000, MySQL 33061, Redis 63790, Mailpit 8025, Vite 5173.

## References
- `docs/ROADMAP.md` (deferred items), `docs/PHASE-0.md`, `docs/adr/`
- `.wolf/cerebrum.md` — decisions + do-not-repeat
