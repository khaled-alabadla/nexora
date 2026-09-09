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

---

## 3. Tenancy

companies

- id
- name
- slug
- status
- created_at
- updated_at

company_user

- company_id
- user_id
- role_id

---

## 4. Users

users

- id
- name
- email
- password
- email_verified_at
- created_at
- updated_at

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
- debit
- credit

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