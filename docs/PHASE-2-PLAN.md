# Phase 2 — Products & Inventory — PLAN (draft, pre-GRILL-ME)

Status: **PLAN**. Not started. Needs GRILL-ME before IMPLEMENT (§7).

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
- Middleware-ordering spike: route-model binding (`{product}` etc.) resolves in
  `SubstituteBindings`, which currently runs **before** the route-level
  `active-company` middleware, so the tenant scope has no context yet (Phase 1
  hit this and used manual `findOrFail`). Decide: add `active-company` to the
  middleware priority list (clean, do once) vs keep manual lookups. → GRILL-ME.

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
POST /inventory/adjustments    { warehouse_id, lines:[{product_id, quantity_delta, unit_cost?, reason}] }  (type adjustment | damage)
POST /inventory/transfers      { from_warehouse_id, to_warehouse_id, lines:[{product_id, quantity}] }      (paired transfer_out/in)
GET  /inventory/low-stock      products at/≤ minimum_stock
```

`InventoryLedger` service is the **only** writer of `inventory_movements` +
`stock`. Phase 3/4 call it for sale/purchase movements.

## 6. Implementation order (vertical slices)

| # | Slice | Delivers | Gate highlights |
|---|---|---|---|
| 2.1 | Products & Categories CRUD | migrations, models, services, endpoints, permissions + shared infra (§3), FE products/categories pages | binding-order spike resolved; SKU/barcode uniqueness; tenant isolation per endpoint |
| 2.2 | Warehouses CRUD | warehouses table/model/service/endpoints/permissions, FE | default-warehouse rule; tenant isolation |
| 2.3 | Ledger + stock projection | `inventory_movements` + `stock`, `InventoryLedger` (txn + row lock), read endpoints, `inventory:reconcile` cmd | projection == ledger **property test**; **concurrency test** (non-transactional); rollback test |
| 2.4 | Adjustments & damage | `POST /inventory/adjustments`, negative-stock rules, FE adjustment form | permission boundary; negative-stock policy enforced |
| 2.5 | Warehouse transfers | paired movements, source-stock validation + lock, FE transfer form | **concurrency test** (two transfers racing one stock row) |
| 2.6 | Low-stock detection | `minimum_stock`, `GET /inventory/low-stock`, dashboard widget | correctness across warehouses |
| 2.7 | Docs + close | DATABASE/API/ARCHITECTURE/SECURITY, ADR (stock projection), PHASE-2.md, ROADMAP; code review; CI floor review | 0 critical/high; docs match code |

## 7. Open decisions — GRILL-ME (recommendation in parens)

1. Stock: maintained projection table vs on-the-fly `SUM` (**projection + reconcile**).
2. `inventory_movements.quantity`: signed +in/-out vs positive + direction-from-type (**signed**).
3. Categories: flat vs `parent_id` adjacency list (**parent_id, no enforced depth**).
4. Negative stock: hard block vs allow via privileged adjustment (**block; adjustments resolve to any ≥ 0; explicit "force correction" flag gated on `inventory.adjust` for true-ups**).
5. Product delete: soft-delete always vs block-with-movements vs archive-only (**soft delete; keep movements; SKU stays reserved**).
6. Route binding: `active-company` into middleware priority list vs manual `findOrFail` (**priority list — spike in 2.1**).
7. Permission granularity: per-action vs coarse `product.manage` (**per-action, like Phase 1 `member.*`**).
8. `damage`: own endpoint vs a `type` on the adjustment endpoint (**type on adjustment**).
9. Default warehouse: model it now (`is_default`, first = default) vs defer (**model now — Phase 3/4 need a target**).
10. Frontend: dedicated `/inventory` nav area + a dashboard inventory summary now (**yes, minimal**).
11. Role → permission matrix for the ~11 new slugs (draft in GRILL-ME; inventory-manager gets all inventory/product/warehouse; sales/purchasing get `*.view`; accountant view-only; employee `product.view`).
12. Coverage floor: keep 85 or raise for business modules (**keep 85 CI floor; aim 90 %+ in the modules**).

## 8. Risks

| Risk | Mitigation |
|---|---|
| Stock projection drifts from the ledger | one `InventoryLedger` writer; projection update always in the movement txn; `inventory:reconcile` + property test in CI |
| Concurrency: lost updates / negative stock races | `lockForUpdate` on `stock` rows inside the txn; dedicated non-`RefreshDatabase` concurrency tests (MySQL, per ADR-0005) |
| Route-model binding runs before tenant context | spike in 2.1; pick priority-list or manual lookup before building 5 resources on it |
| Precision bugs | `DECIMAL(18,4)` for all quantities + unit_cost; model casts; explicit rounding tests; never float |
| Scope creep from Sales/Purchases | movement enum includes purchase/sale/return but **no endpoints** for them this phase |
| `ApiResponse` pagination shape churn | lock the `meta` contract in 2.1 against API.md §3; all later list endpoints reuse it |

## 9. Definition of done (phase)

All slices merged to `develop` then `main`; tag `phase-2`. DATABASE.md /
API.md / ARCHITECTURE.md / SECURITY.md updated; ADR for stock projection;
`PHASE-2.md` written. `inventory:reconcile` reports zero drift on seeded data.
Code review 0 critical / 0 high. CI green. Then request approval for Phase 3.
