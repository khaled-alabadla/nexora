---
description: session handoff, regenerate with /handoff when a quest finishes
budget_tokens: 1000
---
# STATUS — Nexora

> Read FIRST when resuming. Last updated: 2026-09-13

## Mode

Autonomous build (user: full autonomy through all phases). Branch model
`main` / `develop` / `feature/*`, `--no-ff` merges, phase → annotated tag.
`git push` works — `origin` (github.com/khaled-alabadla/nexora) accepts
pushes; CI (`.github/workflows/ci.yml`) runs on `main`/`develop`/PR. No `gh`
CLI — query runs via the GitHub REST API (`curl`, unauthenticated).

## ✅ Done

- **Git hygiene**: reconciled a divergence where a docs commit had landed
  directly on `main` instead of via `develop` (fixed by merging forward);
  verified `main`/`develop`/`phase-1`/`phase-1.1` tags all point to the
  intended commits and match origin.
- **Phase 2 GRILL-ME**: all 12 open decisions in `docs/PHASE-2-PLAN.md` §7
  ratified with the user (every one matched the recommendation) — stock is a
  maintained projection, signed ledger quantity, category adjacency list,
  negative stock blocked (force-flag escape hatch), soft-deleted products,
  active-company added to the middleware priority list, per-action
  permissions, damage-as-adjustment-type, modeled default warehouse, full FE
  each slice.
- **Phase 2.1 — Products & Categories** (merged to `develop` @ `6f636fc`):
  first real `BelongsToCompany` business models. Full CRUD both resources,
  permission-gated, paginated/filtered/searched/sorted products list.
  Middleware-priority fix in `bootstrap/app.php` makes route-model binding
  tenant-scoped (no more manual `findOrFail`). Shared infra:
  `ApiResponse::paginated()`, `App\Support\Http\QueryFilter`. Full SPA pages
  at `/products` and `/categories`, `AppHeader` nav extracted.
  Code-reviewed (`/code-review high`) — 4 findings fixed + regression tests:
  frontend fetching `/categories` without `category.manage`; category
  parent-cycle guard only checked direct self-parent; search didn't escape
  SQL LIKE wildcards; price `max:` validation was 1 digit short of the
  `DECIMAL(18,4)` column.
- Gates: backend 109 tests / 96.7% coverage (Pint + PHPStan L8 clean);
  frontend 70 tests / 93.9% coverage (eslint + tsc + prettier + build clean).

## 🚀 Next quest — Phase 2.2: Warehouses

Per `docs/PHASE-2-PLAN.md` §6 slice table. Modeled: `warehouses`
(company_id, name, location?, `is_default`, status) — first warehouse
created for a company becomes default automatically (ratified §7.9). CRUD +
permissions (`warehouse.view/create/update/delete`, already in
`Permissions.php` + seeder) + FE page, same pattern as 2.1. Then continue
2.3 (ledger + stock projection — the phase's core) → 2.7 (docs/ADR/close).

No open decisions — proceed straight to IMPLEMENT.

## Context

- `develop` clean at `6f636fc`, matches origin. `main` is one slice behind
  (still at the Phase 1 promotion) — Phase 2 stays on `develop` until the
  whole phase is done and promoted (mirrors the Phase 1.1→Phase 1 pattern).
- Docker stack up. Host PHP 8.2 unsupported — backend cmds via
  `docker compose exec -T app …`; coverage needs `XDEBUG_MODE=coverage`.
- Test helper `withoutTenantScope(fn)` in `tests/Pest.php` — required for
  any `BelongsToCompany` factory fixture built outside `actingAs`+real HTTP.
- Ports: API 8000, MySQL 33061, Mailpit 8025, Vite 5173.

## References

- `docs/PHASE-2-PLAN.md` (current phase, ratified decisions in §7)
- `docs/PHASE-1.md`, `docs/adr/0006-tenancy-mechanism.md`, `.wolf/cerebrum.md`
