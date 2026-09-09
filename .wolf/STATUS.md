---
description: session handoff, regenerate with /handoff when a quest finishes
budget_tokens: 1000
---
# STATUS — Nexora

> Single source of truth for resuming work. Read this FIRST when starting a session.
> Update this file at the end of every work phase so the next `/clear` resumes in 1 read.
> Last updated: 2026-09-09

---

## ✅ Done

<!-- Move items here from "🚀 Next phase" when finished. Group by area. -->

- (nothing yet — fill in as work completes)

---

## 🚀 Next phase

**Goal:** Phase 0 — Foundation. Containerized dev env + Laravel 12 backend skeleton (module structure, thin API layer, health endpoint) + React 19 SPA skeleton + full quality gates (Pest on MySQL, PHPStan L6, Pint, Vitest, ESLint) + CI + docs. No business features.

**State:** PLAN + GRILL-ME done. 4 blocking decisions resolved (below). Awaiting user approval of final plan before IMPLEMENT.

### Acceptance criteria
1. `docker compose up` — all services healthy (app php8.4, nginx, mysql:8.4, redis:7, queue, scheduler, node, mailpit).
2. `composer check` (pint --test + phpstan L6 + pest) green; Pest runs against MySQL `nexora_test`.
3. Frontend `npm run lint && typecheck && test && build` green.
4. `GET /api/v1/health` → `{"data":{"status":"ok","db":true,"redis":true},"message":"OK"}`.
5. Frontend page renders health status (CORS verified).
6. CI workflow authored (`.github/workflows/ci.yml`); remote deferred.
7. `.env` gitignored, `.env.example` complete, no secrets tracked.
8. README lets a new dev go clone → running.
9. 13 empty module skeletons load without error.

### Closed decisions (2026-09-09)
- Modules: hand-rolled PSR-4 `Modules\` → `backend/modules/`, explicit providers. No nwidart.
- Auth: Sanctum SPA cookie session (Phase 1 work); tokens available for later external clients. Update API.md.
- Dev env: full Docker; host PHP 8.2 unsupported.
- Test DB: MySQL 8 (`nexora_test`), not SQLite.
- Git: `git init` now, CI authored, remote deferred. No commits unless asked.
- PHPStan L6 now → L8 Phase 1 exit. Coverage floor 60% now.

### Open decisions (deferred, not blocking Phase 0)
- RBAC: `company_user.role_id` implies one role per user per company — confirm in Phase 1.
- Money DECIMAL precision standard — decide Phase 1.
- `inventory_movements.unit_cost` missing (COGS) — address Phase 2/5.
- Soft-delete policy per entity — later phases.

### Scope guard
Phase 0 ships envelope + health check ONLY. Tenant scoping, auth middleware, policies, `BelongsToCompany` = Phase 1.

---

## 📁 Active architecture

- **Stack:** Laravel 12 / PHP 8.4 / MySQL 8.4 / Redis 7 / Sanctum; React 19 + Vite + TS + Tailwind + shadcn/ui + TanStack Query + Zustand. Modular monolith.
- **Layout:** `backend/` (app/, modules/), `frontend/`, `docker/`, `docs/` (+ adr/), `.github/workflows/`, `docker-compose.yml`.
- **Modules (13, empty in P0):** Identity, Companies, Customers, Suppliers, Products, Inventory, Sales, Purchases, Accounting, Expenses, Reports, Notifications, Audit.
- **Patterns:** Controllers thin → Application Services → Domain → Repositories/Eloquent. `/api/v1` prefix. Response envelope `{data,message}` / `{data,meta}`. Error envelope `{message,errors}`. Financial + inventory ops transactional. Never trust client company_id.

---

## ⚠️ External blockers (don't block coding)

- _<env vars, secrets, external accounts, manual steps>_

---

## 🔧 Useful commands

```bash
# add the most-used commands here so the next session has them ready
```

---

## 📚 References (read IF needed)

- `.wolf/cerebrum.md` — User Preferences + Do-Not-Repeat + Decision Log
- `.wolf/anatomy.md` — token-efficient file index
- `.wolf/buglog.json` — known bugs + fixes
