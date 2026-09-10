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

## 5. Products

products

- id
- company_id
- category_id
- sku
- name
- description
- barcode
- unit
- cost_price
- selling_price
- tax_rate
- minimum_stock
- status
- created_at
- updated_at

SKU must be unique within a company.

---

## 6. Warehouses

warehouses

- id
- company_id
- name
- location
- status
- created_at
- updated_at

---

## 7. Inventory

inventory_movements

- id
- company_id
- product_id
- warehouse_id
- type
- quantity
- unit_cost          — DECIMAL(18,4); cost per unit at the time of the movement.
                       Required for inventory valuation / COGS. **Added in Phase 2.**
- reference_type
- reference_id
- created_by
- created_at

Inventory must be calculated from movements or through
a carefully maintained projection derived from the ledger.

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