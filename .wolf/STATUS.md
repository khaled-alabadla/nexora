---
description: session handoff, regenerate with /handoff when a quest finishes
budget_tokens: 1000
---
# STATUS — Nexora

> Read FIRST when resuming. Last updated: 2026-09-14

## Mode

Autonomous build (user: full autonomy through all phases). Branch model
`main` / `develop` / `feature/*`, `--no-ff` merges, phase → annotated tag.
`git push` works — `origin` (github.com/khaled-alabadla/nexora) accepts
pushes; CI (`.github/workflows/ci.yml`) runs on `main`/`develop`/PR. No `gh`
CLI — query runs via the GitHub REST API (`curl`, unauthenticated).

## ✅ Done

- **Phase 2.1 — Products & Categories** (merged @ `6f636fc`): full CRUD,
  permission-gated. Middleware-priority fix makes route-model binding
  tenant-scoped. Code-reviewed, 4 findings fixed.
- **Phase 2.2 — Warehouses** (merged to `develop` @ `c308298`, pushed, CI
  green): `warehouses` CRUD, first-warehouse-becomes-default invariant
  (§7.9), FE `/warehouses` page. `/code-review high` on the initial
  implementation (`ca2007d`) found 3 real backend concurrency bugs + 1
  frontend loading-state bug — fixed in `7c94d60`:
  - `WarehouseService::update()`/`delete()` now re-read the row from inside
    the lock (`requireLocked()`) instead of trusting the (possibly stale,
    e.g. route-model-bound) instance the caller passed in.
  - `create()`'s "first warehouse" check no longer leans on `lockForUpdate`
    gap-locking a zero-row range (isolation-level-dependent, never pinned)
    — every mutation now holds a MySQL named lock (`GET_LOCK`, keyed by
    company id) via `WarehouseService::serialized()`. `SET TRANSACTION
    ISOLATION LEVEL` was tried first; MySQL rejects it mid-transaction,
    which `RefreshDatabase` always has open in tests — see
    `.wolf/cerebrum.md` Do-Not-Repeat.
  - `WarehousesPage` create form now gates on `warehouses.isSuccess`.
  - Docs: `DATABASE.md` §6, `PHASE-2-PLAN.md` §6, buglog bug-049..051.
- Gates (post-2.2): backend 128 tests / 96.9% cov, frontend 80 tests /
  93.0% cov; Pint/PHPStan L8/eslint/tsc/build/composer+npm audit clean.

## 🚀 Next quest — Phase 2.3: Ledger + stock projection

Per `docs/PHASE-2-PLAN.md` §6 (this is the phase's core slice). Build:
`inventory_movements` (signed `quantity`, ratified §7) + `stock` projection
table, `InventoryLedger` service (txn + row lock), read endpoints,
`inventory:reconcile` artisan command. Gate highlights per the plan:
**projection == ledger property test**, **concurrency test**
(non-transactional — this is the first slice that needs a real multi-
connection concurrency-test harness; 2.2's races were caught via code
review + deterministic stale-object unit tests, not live concurrency), and
a rollback test. No open decisions — GRILL-ME already ratified in §7.

After 2.3: 2.4 (adjustments/damage) → 2.5 (transfers, needs its own
concurrency test) → 2.6 (low-stock) → 2.7 (docs/ADR/close, promote
`develop`→`main`, tag `phase-2`).

## Context

- `develop` clean at `c308298`, pushed, CI green. `main` still one phase
  behind (Phase 1 promotion only) — Phase 2 stays on `develop` until the
  whole phase closes (mirrors the Phase 1.1→Phase 1 pattern).
- Docker stack up. Host PHP 8.2 unsupported — backend cmds via
  `docker compose exec -T app …`; coverage needs `XDEBUG_MODE=coverage`.
- Test helper `withoutTenantScope(fn)` in `tests/Pest.php`. New this
  session: to unit-test a service against a stale pre-loaded model (route-
  model-binding staleness), `app(CompanyContext::class)->set($company)`
  then call the service directly — no HTTP round trip needed.
- Ports: API 8000, MySQL 33061, Mailpit 8025, Vite 5173.

## References

- `docs/PHASE-2-PLAN.md` (current phase, ratified decisions in §7)
- `docs/DATABASE.md` §6 (warehouses locking), `.wolf/cerebrum.md`
