# Phase 2 — Products & Inventory — PLAN

Status: **GRILL-ME complete (2026-09-13)** — all §7 decisions ratified with the
user, every one matching the recommended option. Ready for IMPLEMENT.

## 1. Scope (from ROADMAP + PRD + DATABASE.md)

Products, Categories, Warehouses, Stock, Inventory ledger, Stock adjustments,
Warehouse transfers, Low-stock detection. Modules `Products` and `Inventory`
(both scaffolded, empty). This phase creates the **first real
`BelongsToCompany` business models**.

Out of scope (defined but not wired here): `purchase` / `sale` / `return`
movement types — written by Phase 3 (Sales) and Phase 4 (Purchases). Inventory
alerts / notifications — Phase 7. Full audit module — Phase 7 (`created_by` +
the append-only ledger are the audit trail for now).

## 2. Reuse from Phase 1 (no new patterns needed)

- Module = `<Name>ServiceProvider` + `Routes/api.php` behind
  `auth:sanctum` + `active-company`; `permission:<slug>` middleware + a Gate
  per slug; new slugs added to `App\Support\Authorization\Permissions` and the
  role map in `RolesAndPermissionsSeeder`.
- `BelongsToCompany` trait on every table (`company_id` never `$fillable`,
  forced from `CompanyContext` on create, global scope on read).
- Thin controllers → services → Eloquent. `DB::transaction` for every stock
  mutation. `ApiResponse` envelopes, FormRequests, `JsonResource`.
- Tests: `RefreshDatabase`, `companyWithOwner()` / `actingInCompany()` helpers,
  MySQL (`nexora_test`). Per CLAUDE.md every feature covers happy / failure /
  **authz per role** / **tenant isolation per endpoint** / validation / DB
  integrity / business rules / **concurrency** / **transaction rollback**.
- Gates: Pint, PHPStan **L8**, Pest ≥ 85 % coverage, frontend
  eslint/tsc/prettier/vitest/build, CI green, code review 0 critical/high.

## 3. New shared infrastructure (build in slice 2.1)

- `ApiResponse::paginated(LengthAwarePaginator)` → `{data, message, meta:{…}}`
  per `docs/API.md` §3 (first list endpoint needs it).
- `App\Support\Http\QueryFilter` — whitelisted filter / search / sort helper
  (API.md §6–8). Hand-rolled, no package.
- Middleware-ordering fix (ratified §7.6): add `active-company` (and
  `auth:sanctum`) to the middleware priority list in `bootstrap/app.php` so
  `SubstituteBindings` runs after tenant context is bound. Do this first in
  2.1, then implicit route-model binding (`{product}` etc.) is tenant-scoped
  automatically — no more manual `findOrFail` lookups.

## 4. Data model

| Table | Module | Key columns | Constraints |
|---|---|---|---|
| `product_categories` | Products | company_id, name, parent_id? (self-FK), status | unique (company_id, name) |
| `products` | Products | company_id, category_id (nullable), sku, name, description, barcode (nullable), unit, cost_price `DECIMAL(18,4)`, selling_price `DECIMAL(18,4)`, tax_rate `DECIMAL(18,4)`, minimum_stock `DECIMAL(18,4)`, status, soft deletes | unique (company_id, sku); unique (company_id, barcode) where not null |
| `warehouses` | Inventory | company_id, name, location (nullable), is_default, status | unique (company_id, name); one `is_default` per company |
| `inventory_movements` | Inventory | company_id, product_id, warehouse_id, type (`purchase\|sale\|return\|adjustment\|transfer_in\|transfer_out\|damage`), quantity `DECIMAL(18,4)`, unit_cost `DECIMAL(18,4)`, reference_type/reference_id (nullable), note, created_by, created_at only (**append-only, immutable**) | indexes on (company_id, product_id, warehouse_id), (company_id, product_id), (warehouse_id), (type), (created_at) |
| `stock` | Inventory | company_id, product_id, warehouse_id, quantity `DECIMAL(18,4)` | unique (company_id, product_id, warehouse_id) |

`inventory_movements.unit_cost` is the column `docs/DATABASE.md` flagged as
"add in Phase 2".

**`stock` is a maintained projection** of the ledger (the ledger is the source
of truth). Updated inside the same transaction as each movement, with
`lockForUpdate` on the row. An `inventory:reconcile` artisan command + a
property test assert `stock.quantity == SUM(movements.quantity)` per
(product, warehouse). → GRILL-ME confirms projection vs on-the-fly SUM.

## 5. Endpoints (all under `/api/v1`, `active-company`)

**Products** — `product.view` / `product.create` / `product.update` /
`product.delete`

```
GET/POST/GET{id}/PUT{id}/DELETE{id}  /products     (list: paginated, ?status ?category_id ?search ?sort)
GET/POST/PUT{id}/DELETE{id}          /categories   category.manage
```

**Warehouses** — `warehouse.view` / `warehouse.create` / `warehouse.update` /
`warehouse.delete`

```
GET/POST/GET{id}/PUT{id}/DELETE{id}  /warehouses
```

**Inventory** — `inventory.view` / `inventory.adjust` / `inventory.transfer`

```
GET  /inventory/stock          current levels (product × warehouse), paginated, ?warehouse_id ?product_id ?low_stock
GET  /inventory/movements      the ledger, paginated, filtered
POST /inventory/adjustments    { warehouse_id, type: adjustment|damage, force?, lines:[{product_id, quantity_delta, unit_cost?, reason}] }
POST /inventory/transfers      { from_warehouse_id, to_warehouse_id, lines:[{product_id, quantity}] }      (paired transfer_out/in; never accepts force)
GET  /inventory/low-stock      products at/≤ minimum_stock
```

`InventoryLedger` service is the **only** writer of `inventory_movements` +
`stock`. Phase 3/4 call it for sale/purchase movements.

## 6. Implementation order (vertical slices)

| # | Slice | Delivers | Gate highlights |
|---|---|---|---|
| 2.1 | ✅ Products & Categories CRUD (2026-09-13) | migrations, models, services, endpoints, permissions + shared infra (§3), FE products/categories pages | binding-order spike resolved; SKU/barcode uniqueness; tenant isolation per endpoint — 107 backend tests, 66 frontend tests, both gates green |
| 2.2 | ✅ Warehouses CRUD (2026-09-14) | warehouses table/model/service/endpoints/permissions, FE | default-warehouse rule; tenant isolation; concurrent-write correctness (`GET_LOCK` serialization + re-read-after-lock, found in code review) — 128 backend tests, 80 frontend tests, both gates green |
| 2.3 | ✅ Ledger + stock projection (2026-09-14) | `inventory_movements` + `stock`, `InventoryLedger` (txn + row lock + unique-constraint race safety, ADR-0007), read endpoints, `inventory:reconcile` cmd, FE read-only inventory page | projection == ledger **property test**; **concurrency test** (2 real MySQL connections, non-RefreshDatabase); rollback test; tenant isolation — 158 backend tests, 86 frontend tests, both gates green |
| 2.4 | Adjustments & damage | `POST /inventory/adjustments`, negative-stock rules, FE adjustment form | permission boundary; negative-stock policy enforced |
| 2.5 | Warehouse transfers | paired movements, source-stock validation + lock, FE transfer form | **concurrency test** (two transfers racing one stock row) |
| 2.6 | Low-stock detection | `minimum_stock`, `GET /inventory/low-stock`, dashboard widget | correctness across warehouses |
| 2.7 | Docs + close | DATABASE/API/ARCHITECTURE/SECURITY, ADR (stock projection), PHASE-2.md, ROADMAP; code review; CI floor review | 0 critical/high; docs match code |

## 7. Decisions — RATIFIED (GRILL-ME, 2026-09-13)

1. **Stock**: maintained projection table + `inventory:reconcile` + drift
   property test. Ledger (`inventory_movements`) stays the source of truth.
2. **`inventory_movements.quantity`**: **signed** (+in / −out). Current stock
   is `SUM(quantity)` per (product, warehouse) — that's exactly what the
   projection caches.
3. **Categories**: `parent_id` adjacency list (nullable self-FK), no enforced
   depth limit.
4. **Negative stock**: **blocked by default**. Adjustments/transfers may not
   drop `stock.quantity` below 0 unless the caller passes `force: true` on
   `POST /inventory/adjustments`, still gated on `inventory.adjust` — a true-up
   correction path, not a loophole for normal operations. Transfers never
   accept `force` (a transfer never fabricates stock).
5. **Product delete**: soft delete always; `inventory_movements` /
   `stock` rows are untouched; SKU/barcode stay reserved (unique index
   includes soft-deleted rows, like `Company.slug`).
6. **Route binding**: add `active-company` (and `auth:sanctum`) to the
   Laravel middleware **priority list** in `bootstrap/app.php` so
   `SubstituteBindings` runs after tenant context is bound — spike this first
   in 2.1, then `{product}`/`{warehouse}` etc. type-hint straight to
   tenant-scoped models like any other Eloquent binding.
7. **Permission granularity**: per-action (`product.view/create/update/delete`,
   `category.manage`, `warehouse.view/create/update/delete`,
   `inventory.view/adjust/transfer`) — consistent with Phase 1's `member.*`.
8. **Damage**: a `type` on `POST /inventory/adjustments`
   (`adjustment` | `damage`), not a separate endpoint.
9. **Default warehouse**: `warehouses.is_default` (unique-per-company via a
   partial index / app-level invariant), first warehouse created for a company
   becomes default automatically.
10. **Frontend**: full UI this phase — product/category/warehouse CRUD pages,
    stock view, adjustment + transfer forms, low-stock widget on the
    dashboard. Matches how Phase 1.1 shipped backend + SPA together.

### Role → permission matrix (ratified default; adjust only if it misfires in testing)

| Role | product.* | category.manage | warehouse.* | inventory.view | inventory.adjust | inventory.transfer |
|---|---|---|---|---|---|---|
| owner | ✅ (implicit — all) | | | | | |
| administrator | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| inventory-manager | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| accountant | view | | | view | | |
| sales-manager | view | | | view | | |
| sales-rep | view | | | | | |
| purchasing-manager | view | | view | view | | |
| employee | view | | | | | |

11. **Coverage floor**: keep the CI floor at 85 %; aim for 90 %+ within
    `Modules\Products` / `Modules\Inventory` given they carry the ledger.

## 8. Risks

| Risk | Mitigation |
|---|---|
| Stock projection drifts from the ledger | one `InventoryLedger` writer; projection update always in the movement txn; `inventory:reconcile` + property test in CI |
| Concurrency: lost updates / negative stock races | `lockForUpdate` on `stock` rows inside the txn; dedicated non-`RefreshDatabase` concurrency tests (MySQL, per ADR-0005) |
| Route-model binding runs before tenant context | fixed via middleware priority list (§7.6), first thing in 2.1, before any resource controller is built on it |
| Precision bugs | `DECIMAL(18,4)` for all quantities + unit_cost; model casts; explicit rounding tests; never float |
| Scope creep from Sales/Purchases | movement enum includes purchase/sale/return but **no endpoints** for them this phase |
| `ApiResponse` pagination shape churn | lock the `meta` contract in 2.1 against API.md §3; all later list endpoints reuse it |

## 9. Definition of done (phase)

All slices merged to `develop` then `main`; tag `phase-2`. DATABASE.md /
API.md / ARCHITECTURE.md / SECURITY.md updated; ADR for stock projection;
`PHASE-2.md` written. `inventory:reconcile` reports zero drift on seeded data.
Code review 0 critical / 0 high. CI green. Then request approval for Phase 3.
