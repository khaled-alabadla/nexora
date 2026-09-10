---
description: session handoff, regenerate with /handoff when a quest finishes
budget_tokens: 1000
---
# STATUS — Nexora

> Read FIRST when resuming. Last updated: 2026-09-10

## Mode

Autonomous build (user: full autonomy through all phases). Branch model
`main` / `develop` / `feature/*`, local `--no-ff` merges, phase → tag.
**`git push` is BLOCKED** (auto-mode classifier; no `gh`/token) — history is
local only. CI (`.github/workflows/ci.yml`) runs on first push; the local full
quality suite is the working gate.

## ✅ Phase 0 — Foundation — COMPLETE (tag `phase-0`, on `main` + `develop`)

Containerized modular monolith. Laravel 12 / PHP 8.4, `Modules\` PSR-4 (13
modules), thin API (`ApiResponse`, JSON exceptions), `GET /api/v1/health`,
Sanctum SPA cookie config. React 19 / Vite / TS strict, Tailwind v4, TanStack
Query, Zustand, typed API client. Compose: app/nginx/queue/scheduler/mysql/
redis/node/mailpit. ADRs 0001–0005.

## ✅ Phase 1.1 — Identity Foundation — COMPLETE (tag `phase-1.1`, on `develop`)

Merged `347ea90`. Not on `main` — full-phase promotion needs user sign-off.

- **Identity module**: register (txn: user + first company + Owner membership),
  login (session regen, 5/email+IP lockout), logout, `GET /auth/me` (session
  payload), password reset (broker, no user enumeration), email verification
  (signed link → SPA redirect) + resend. `IdentityServiceProvider` points
  notification URLs at `FRONTEND_URL`.
- **Companies module**: `Company`/`Role`/`Permission`/`CompanyUser`/
  `CompanyInvitation` models; `CompanyProvisioner` / `CompanyMembershipService`
  / `CompanyInvitationService`; endpoints for companies list/create, switch
  active, company show/update, members list/role/remove, invitations
  list/send/revoke/accept, `GET /roles`. 8 system roles + 5 permissions seeded
  in a migration.
- **Tenancy (ADR-0006)**: `CompanyContext` singleton (throws when unset),
  `BelongsToCompany` trait (global scope + forced `company_id` +
  `withoutCompanyScope`), `SetActiveCompany` + `EnsurePermission` middleware,
  per-permission Gates. `CompanyInvitation` is the one model using the trait.
- **Frontend**: react-router-dom; auth pages (login/register/forgot/reset),
  `RequireAuth`, dashboard, company switcher, members panel (permission-gated),
  invitation acceptance, verify-email banner. `lib/api.ts` gained
  put/patch/delete + 204 handling.
- **Gates**: PHPStan **6 → 8** (exit gate); Pint clean; Pest 80/299 @ 95.9%;
  CI backend coverage floor 60 → 85. Frontend eslint/tsc/prettier/vitest 51 @
  95%/82%, build clean. `composer audit` / `npm audit` clean.
- **Security review**: 0 critical / 0 high. Lows tracked in `docs/PHASE-1.md`
  (IP-rotation login-throttle bypass → Phase 8; GET-clears-stale-pointer).
- **Test infra**: `phpunit.xml` `SESSION_DRIVER=database` + stateful `Origin`
  so the SPA cookie flow runs end-to-end. Helpers in `tests/Pest.php`
  (`companyWithOwner`, `addMember`, `actingInCompany`). Frontend `test/fetchStub.ts`
  (fetch router) + `MemoryRouter` in `renderWithProviders`.

## 🚀 Next quest — Phase 1 close-out / Phase 2

The ROADMAP Phase 1 checklist is fully delivered by slice 1.1. Options for the
user to decide:

1. **Promote Phase 1 to `main` + tag `phase-1`** — needs explicit approval
   (production line). Nothing code-wise blocks it.
2. **Phase 1.2 polish** (deferred, non-blocking): per-company custom roles,
   invitation-resend UI, pending-invite management polish.
3. **Start Phase 2 — Products & Inventory** — needs PLAN → GRILL-ME. Adds the
   first real `BelongsToCompany` business models; `inventory_movements.unit_cost`
   (18,4) to add per `docs/DATABASE.md`.

## Context

- Branch `develop` clean; `feature/1.1-identity-foundation` merged (not deleted).
- Docker stack is up. Host PHP 8.2 is unsupported — all backend cmds via
  `docker compose exec -T app …`. Coverage needs `XDEBUG_MODE=coverage`.
- `make check` = pint + phpstan + pest. Ports: API 8000, MySQL 33061,
  Mailpit 8025, Vite 5173.

## References

- `docs/PHASE-1.md`, `docs/PHASE-0.md`, `docs/adr/0006-tenancy-mechanism.md`
- `docs/ROADMAP.md` (deferred items), `.wolf/cerebrum.md` (decisions)
