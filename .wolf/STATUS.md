---
description: session handoff, regenerate with /handoff when a quest finishes
budget_tokens: 1000
---
# STATUS — Nexora

> Read FIRST when resuming. Last updated: 2026-09-10 (evening)

## Mode

Autonomous build (user: full autonomy through all phases). Branch model
`main` / `develop` / `feature/*`, `--no-ff` merges, phase → annotated tag.
**`git push` now works** — `origin` (github.com/khaled-alabadla/nexora) accepts
pushes; CI (`.github/workflows/ci.yml`) runs on `main`/`develop`/PR. No `gh`
CLI — query runs via the GitHub REST API (`curl`, unauthenticated). Local
`make check` / `make check-frontend` remain the fast gate.

## ✅ Phase 0 — Foundation — COMPLETE (tag `phase-0`, on `main` + `develop`)

Containerized modular monolith. Laravel 12 / PHP 8.4, `Modules\` PSR-4 (13
modules), thin API (`ApiResponse`, JSON exceptions), `GET /api/v1/health`,
Sanctum SPA cookie config. React 19 / Vite / TS strict, Tailwind v4, TanStack
Query, Zustand, typed API client. Compose: app/nginx/queue/scheduler/mysql/
redis/node/mailpit. ADRs 0001–0005.

## ✅ Phase 1 — Identity & Multi-Tenancy — COMPLETE & PROMOTED

On `main` + `develop`. Tags `phase-1.1` (slice) and `phase-1` (`main`, `d0a1c49`).
**CI green on first push** (run #3, after a composer-audit fix — the security
job needed `composer audit --locked` since it has no `composer install`).

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

## 🚀 Next quest — Phase 2: Products & Inventory

**PLAN drafted** → `docs/PHASE-2-PLAN.md`. Next step is **GRILL-ME** on that
doc's §7 (12 open decisions), then IMPLEMENT slice 2.1.

- Modules `Products` + `Inventory` (both scaffolded, empty). First real
  `BelongsToCompany` business models.
- Slices: 2.1 Products/Categories CRUD (+ shared infra: `ApiResponse::paginated`,
  query-filter helper, route-binding-order spike) → 2.2 Warehouses → 2.3 ledger
  + `stock` projection + `inventory:reconcile` → 2.4 adjustments → 2.5 transfers
  → 2.6 low-stock → 2.7 docs/close.
- Key open decisions: stock projection vs on-the-fly SUM; signed vs
  positive+direction quantity; category tree; negative-stock policy; route
  binding via middleware priority vs manual lookup. See plan §7.
- Add `inventory_movements.unit_cost` `DECIMAL(18,4)` (flagged in DATABASE.md).
- Do NOT start implementing until GRILL-ME is done with the user.

## Context

- `develop` = `main` + this handoff. `feature/1.1-identity-foundation` merged
  (kept, also on origin).
- Docker stack up. Host PHP 8.2 unsupported — backend cmds via
  `docker compose exec -T app …`; coverage needs `XDEBUG_MODE=coverage`.
- Test infra quirk: `phpunit.xml` uses `SESSION_DRIVER=database` + a stateful
  `Origin` header (Sanctum SPA cookie flow). See
  `.wolf/` memory / `docs/PHASE-1.md`.
- Ports: API 8000, MySQL 33061, Mailpit 8025, Vite 5173.

## References

- `docs/PHASE-2-PLAN.md` (next), `docs/PHASE-1.md`, `docs/PHASE-0.md`
- `docs/adr/0006-tenancy-mechanism.md`, `docs/ROADMAP.md`, `.wolf/cerebrum.md`
