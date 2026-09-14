# Nexora — Database Design

## 1. Database

MySQL 8+

---

## 2. Core Principles

- Use foreign keys.
- Use appropriate indexes.
- Use DECIMAL for monetary values.
- Never use FLOAT for financial amounts.
- Use timestamps where appropriate.
- Enforce important constraints at database level.
- Tenant-owned data must contain company_id where appropriate.

### Monetary precision (ratified — Phase 1, 2026-09-10)

Standard column types, mirrored in `backend/config/nexora.php`
(`money.precision` 18, `amount_scale` 2, `rate_scale` 4):

| Use | Type |
|---|---|
| Amounts (totals, balances, line amounts) | `DECIMAL(18, 2)` |
| Unit prices, tax rates, FX rates, quantities needing fractions | `DECIMAL(18, 4)` |

All money math is done with these scales; never with floats.

---

## 3. Tenancy & authorization (Phase 1)

Mechanism: [ADR-0006](adr/0006-tenancy-mechanism.md) — one database, row-level
`company_id` scoping via `App\Support\Tenancy\BelongsToCompany` + a
request-scoped `CompanyContext`.

**companies** — the tenant

- id, name, slug (unique), status (`active` | `suspended`)
- timestamps, `deleted_at` (soft delete)

**roles** — system-defined (Phase 1 has no per-company custom roles)

- id, slug (unique), name, level (`unsignedSmallInteger`; higher = more
  privileged), is_system, timestamps
- 8 rows seeded by migration `…_seed_roles_and_permissions`: owner (100),
  administrator (80), accountant / sales-manager / inventory-manager /
  purchasing-manager (50), sales-rep (20), employee (10)

**permissions** — the catalogue (`App\Support\Authorization\Permissions`)

- id, slug (unique), name, group (indexed), timestamps
- Phase 1: `company.update`, `member.view`, `member.invite`,
  `member.role.update`, `member.remove`

**role_permission** — composite PK `(role_id, permission_id)`, both cascade.
Owner is omitted here — it implicitly holds every permission.

**company_user** — membership: one role per user per company

- id, company_id, user_id, role_id (`restrictOnDelete`)
- unique `(company_id, user_id)`; index `(user_id)`

**company_invitations**

- id, company_id, role_id, invited_by (→ users), email
- token — **sha256 hash** of the emailed token, never the raw value; `token`
  is `$hidden`
- expires_at, accepted_at (nullable), timestamps
- unique `(company_id, email)`

---

## 4. Users

users

- id, name, email (unique), password, email_verified_at, remember_token
- current_company_id — nullable FK → companies, `nullOnDelete` (Phase 1). The
  active company; membership is re-checked by `SetActiveCompany` on every
  tenant-scoped request.
- timestamps

---

## 5. Products (Phase 2.1)

Tenant-scoped via `BelongsToCompany` — see [ADR-0006](adr/0006-tenancy-mechanism.md).

**product_categories**

- id, company_id, parent_id (nullable self-FK, `nullOnDelete` — adjacency
  list, no enforced nesting depth), name, status (`active` \| `inactive`),
  timestamps
- unique `(company_id, name)`
- Hard-deleted: no ledger references a category, so a delete just
  `nullOnDelete`s its children and any products pointing at it.

**products**

- id, company_id, category_id (nullable, `nullOnDelete`), sku, name,
  description (nullable), barcode (nullable), unit (default `pcs`),
  cost_price `DECIMAL(18,4)`, selling_price `DECIMAL(18,4)`,
  tax_rate `DECIMAL(18,4)`, minimum_stock `DECIMAL(18,4)`, status
  (`active` \| `inactive`), timestamps, `deleted_at` (soft delete)
- unique `(company_id, sku)`, unique `(company_id, barcode)`
- SKU and barcode are unique **within a company**. A soft-deleted product
  keeps its SKU reserved — a new product cannot reuse it while the deleted
  row still exists (PHASE-2-PLAN.md §7.5).

---

## 6. Warehouses (Phase 2.2)

Tenant-scoped via `BelongsToCompany`.

**warehouses**

- id, company_id, name, location (nullable), is_default (boolean, default
  false), status (`active` \| `inactive`), timestamps
- unique `(company_id, name)`; index `(company_id, is_default)`

Exactly one warehouse is the company's default once it has at least one —
**enforced in `Modules\Inventory\Services\WarehouseService`, not the
database**: MySQL has no partial/filtered unique index, so a plain unique
constraint on `is_default` can't express "unique only where true". Every
write (create/update/delete) goes through the service, inside a
`DB::transaction` that `lockForUpdate`s the company's warehouse rows *and*
holds a MySQL named lock (`GET_LOCK`/`RELEASE_LOCK`) keyed by company id for
the duration — the named lock is what actually serializes concurrent
default-flips (a `lockForUpdate` matching zero rows, as when checking "is
this the company's first warehouse", only gap-locks under REPEATABLE READ,
which the app never explicitly pins). Because the model instance a write
operates on may have been loaded before a concurrent write committed, the
service always re-reads the row from inside the lock rather than trusting
the instance it was given. Rules:

- The first warehouse a company creates becomes the default automatically
  (client input for `is_default` is ignored for that first row).
- Setting `is_default: true` on another warehouse atomically clears the
  previous default.
- A default warehouse cannot be un-defaulted directly (`422`) — another
  warehouse must be made default first.
- A default warehouse cannot be deleted while other warehouses exist
  (`422`); it *can* be deleted when it is the company's only warehouse
  (leaving zero — the next one created becomes default again).

---

## 7. Inventory

inventory_movements (append-only ledger — the source of truth, **Phase 2.3**)

- id
- company_id
- product_id           — `restrictOnDelete` (a product with movement history can't be hard-deleted)
- warehouse_id         — `restrictOnDelete`, same reasoning
- type                 — `purchase|sale|return|adjustment|transfer_in|transfer_out|damage`;
                          purchase/sale/return are modeled now but unreachable until Phase 3/4
- quantity             — DECIMAL(18,4); **signed**, +in / −out (§7.2). Current stock for a
                          (product, warehouse) pair is `SUM(quantity)` — see `stock` below.
- unit_cost            — DECIMAL(18,4), nullable; cost per unit at the time of the movement.
                          Required for inventory valuation / COGS. **Added in Phase 2.**
- reference_type / reference_id — nullable, polymorphic-style link to the record that
                          caused the movement (a sale, purchase, transfer — Phase 3/4/2.5)
- note                 — nullable
- created_by           — nullable FK to `users`, `nullOnDelete`
- created_at only — **no `updated_at`, no soft deletes.** A row is never
  modified or removed once written; correcting a mistake means recording a
  new offsetting movement, never editing history.

stock (maintained projection of the ledger, **Phase 2.3**)

- id, company_id, product_id (`restrictOnDelete`), warehouse_id (`restrictOnDelete`)
- quantity — DECIMAL(18,4), default 0
- timestamps
- unique `(company_id, product_id, warehouse_id)`

The only writer of either table is
`Modules\Inventory\Services\InventoryLedger::record()` (and, for `stock`
only, the `inventory:reconcile` command repairing drift) — never insert into
these directly. Every write happens inside `DB::transaction()` with
`lockForUpdate()` on the `stock` row; the *first* movement for a
(product, warehouse) pair additionally relies on the table's real unique
constraint (not gap-locking — see ADR-0007) to stay race-safe regardless of
transaction isolation level. Negative stock is blocked by default; only
`adjustment`/`damage` movements may pass `force: true` to bypass that guard
(§7.4). All quantity arithmetic is done as decimal strings (`bcadd`/
`bccomp`), never PHP floats.

---

## 8. Customers

customers

- id
- company_id
- name
- email
- phone
- address
- tax_number
- status
- created_at
- updated_at

---

## 9. Suppliers

suppliers

- id
- company_id
- name
- email
- phone
- address
- tax_number
- status
- created_at
- updated_at

---

## 10. Accounting

accounts

- id
- company_id
- code
- name
- type
- parent_id

journal_entries

- id
- company_id
- reference
- description
- entry_date
- status
- created_by

journal_entry_lines

- id
- journal_entry_id
- account_id
- debit             — DECIMAL(18,2)
- credit            — DECIMAL(18,2)

Tenant scoping: a line is reachable only via `journal_entry_id`. Whether to
denormalise `company_id` onto the line (faster tenant-scoped reporting) or
always join through `journal_entries` is decided in **Phase 5**.

Invariant:

SUM(debit) = SUM(credit)

for every posted journal entry.

---

## 11. Indexing

Indexes should be added based on:

- tenant filtering
- foreign keys
- search requirements
- frequent sorting
- reporting queries

Avoid adding indexes blindly.

---

## 12. Data Integrity

Important financial and inventory operations must use
database transactions.

Concurrent stock updates must use appropriate locking
where required.