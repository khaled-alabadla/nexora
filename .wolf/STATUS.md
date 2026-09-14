---
description: session handoff, regenerate with /handoff when a quest finishes
budget_tokens: 1000
---
# STATUS — Nexora

> Read FIRST when resuming. Last updated: 2026-09-14

## Mode

Autonomous build (user: full autonomy through all phases, explicitly told
to proceed slice-to-slice without stopping for approval). Branch model
`main` / `develop` / `feature/*`, `--no-ff` merges, phase → annotated tag.
`git push` works; CI (`.github/workflows/ci.yml`) runs on `main`/`develop`/
PR (not plain feature-branch pushes — no PRs opened, matches 2.1/2.2/2.3
precedent of merging locally then pushing `develop`). No `gh` CLI — query
runs via the GitHub REST API (`curl`, unauthenticated).

## ✅ Done

- **Phase 2.2 — Warehouses** (merged @ `c308298`): CRUD, code-reviewed.
- **Phase 2.3 — Inventory Ledger + Stock Projection** (merged to `develop`
  @ `b325a54`, pushed, CI green): `inventory_movements` (append-only,
  signed `DECIMAL(18,4)`) + `stock` (maintained projection, unique
  `(company_id, product_id, warehouse_id)`). `InventoryLedger` is the sole
  writer — locks/race-safely-creates the stock row, enforces negative-stock
  policy (force only for adjustment/damage), validates quantity sign vs
  movement type, guards against a foreign product/warehouse. `GET
  /inventory/stock` + `/inventory/movements` (paginated, filtered,
  `inventory.view`-gated). `inventory:reconcile` command (locked
  recompute-then-write per row — never touches ledger history). FE
  read-only `/inventory` page. See `docs/adr/0007-stock-projection.md` for
  why this needed no named lock (unlike `warehouses.is_default` in 2.2) —
  `stock`'s real unique constraint makes plain `lockForUpdate` +
  catch-duplicate-retry race-safe under any isolation level.
  - `/code-review high` ran twice (initial + a fix-verification pass) and
    found 6 issues, all fixed: a test that could leave a row lock held on
    assertion failure, frontend queries not gated on `inventory.view`,
    `--dry-run` N+1 queries, a missing sort tiebreaker for same-second
    ledger entries, a missing quantity-sign/type consistency check, a
    redundant DB index.
  - 2 real-MySQL concurrency tests (plain PHPUnit, not RefreshDatabase —
    two genuine DB connections) prove the row lock and the unique-
    constraint race guard actually block a second writer.
  - Verified end-to-end against the real dev server via curl (register →
    create product/warehouse → record a movement → read both endpoints →
    reconcile), not just Pest.
- Gates (post-2.3): backend 160 tests / 96.5% cov, frontend 86 tests /
  93.1% cov; Pint/PHPStan L8/eslint/tsc/prettier/build/composer+npm audit
  clean.

## 🚀 Next quest — Phase 2.4: Adjustments & damage

Per `docs/PHASE-2-PLAN.md` §6. Build: `POST /inventory/adjustments`
(`inventory.adjust`) — `{ warehouse_id, type: adjustment|damage, force?,
lines:[{product_id, quantity_delta, unit_cost?, reason}] }`, each line
calling `InventoryLedger::record()` (already built, already validates
sign/type/negative-stock/force-scoping — 2.4 is mostly a thin
controller+FormRequest+FE form over it). FE adjustment form. Gate
highlights: permission boundary test, negative-stock policy enforced
end-to-end through the real endpoint (2.3 only tested the service
directly). No open decisions — GRILL-ME already ratified in §7.

After 2.4: 2.5 (transfers — paired transfer_out/in, its own concurrency
test: two transfers racing one stock row) → 2.6 (low-stock) → 2.7
(docs/ADR/close, promote `develop`→`main`, tag `phase-2`).

## Context

- `develop` clean at `b325a54`, pushed, CI green. `main` still one phase
  behind — Phase 2 stays on `develop` until the whole phase closes.
- Docker stack up. Host PHP 8.2 unsupported — backend cmds via
  `docker compose exec -T app …`; coverage needs `XDEBUG_MODE=coverage`.
  **Never run `php artisan test` twice concurrently** (same shared
  `nexora_test` DB — collides, produces spurious failures).
- For a real (non-RefreshDatabase) concurrency test: plain PHPUnit class
  extending `Tests\TestCase`, two DB connection *names* on the same
  database, `SET SESSION innodb_lock_wait_timeout=1` on the second. See
  `StockConcurrencyTest` / `.wolf/cerebrum.md`.
- Ports: API 8000, MySQL 33061, Mailpit 8025, Vite 5173.

## References

- `docs/PHASE-2-PLAN.md` (current phase, ratified decisions in §7)
- `docs/adr/0007-stock-projection.md`, `.wolf/cerebrum.md`
