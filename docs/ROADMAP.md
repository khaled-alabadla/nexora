# Nexora — Development Roadmap

## Development Workflow

Every major phase follows:

PLAN
→ GRILL-ME
→ IMPLEMENT
→ TEST
→ CODE-REVIEW
→ FIX
→ TEST AGAIN
→ DOCUMENT
→ COMPLETE

A phase cannot be completed while Critical or High findings remain.

The next major phase requires explicit approval.

---

# Phase 0 — Foundation ✅ COMPLETE (2026-09-09)

Goal:

Prepare the project infrastructure.

Delivered:

- Monorepo: `backend/` (Laravel 12, PHP 8.4), `frontend/` (React 19 + Vite + TS),
  `docker/`, `docs/` (+ `adr/`)
- Docker Compose stack (app, nginx, queue, scheduler, mysql, redis, node, mailpit)
- Modular monolith: `Modules\` PSR-4, `make:module` generator, 13 empty modules
  (ADR-0002)
- Thin API layer: `/api/v1`, `ApiResponse` envelopes, JSON exceptions,
  `GET /api/v1/health`
- Sanctum SPA cookie auth configured (ADR-0004); CORS allow-list
- Quality gates: Pest on MySQL (ADR-0005), Larastan level 6, Pint;
  ESLint + tsc + Prettier + Vitest
- CI: `.github/workflows/ci.yml` (backend, frontend, security jobs)
- Docs: ADR 0001–0005, `DEVELOPMENT.md`, `TESTING.md`, `PHASE-0.md`

Deferred to later phases (noted here so they are not forgotten):

- PHPStan level 6 → **level 8 is a Phase 1 exit criterion**
- Coverage floor 60% now; raise per phase (target 85%+ for business modules)
- `inventory_movements.unit_cost` is missing from `docs/DATABASE.md` and is
  required for inventory valuation / COGS — **add in Phase 2**
- Money `DECIMAL` precision standard drafted in `config/nexora.php`
  (18,2 amounts / 18,4 rates) — **ratify in Phase 1**
- `journal_entry_lines` has no `company_id` — decide denormalise vs. join in
  **Phase 5**
- `company_user.role_id` implies one role per user per company — confirm in
  **Phase 1**
- MySQL image pinned to 8.0 (8.4 pull was unavailable) — bump when possible

No business features were implemented.

---

# Phase 1 — Identity & Multi-Tenancy

- Registration
- Login
- Logout
- Password reset
- Email verification
- Companies
- Company memberships
- Active company
- Roles
- Permissions
- Authorization
- Tenant isolation

---

# Phase 2 — Products & Inventory

- Products
- Categories
- Warehouses
- Stock
- Inventory ledger
- Stock adjustments
- Warehouse transfers
- Low-stock detection

---

# Phase 3 — Sales

- Customers
- Quotations
- Sales orders
- Invoices
- Payments
- Returns
- Sales reports

---

# Phase 4 — Purchases

- Suppliers
- Purchase orders
- Goods receiving
- Purchase invoices
- Supplier payments
- Purchase returns

---

# Phase 5 — Accounting

- Chart of accounts
- Journal entries
- Journal entry lines
- Accounts receivable
- Accounts payable
- General ledger
- Financial periods
- Double-entry validation

---

# Phase 6 — Reports & Dashboard

- Dashboard
- Sales reports
- Purchase reports
- Inventory reports
- P&L
- Balance sheet
- Customer balances
- Supplier balances
- Expense reports
- Tax reports

---

# Phase 7 — Automation

- Notifications
- Email
- Scheduled jobs
- Low-stock alerts
- Overdue invoice alerts
- Daily summaries
- Async report generation

---

# Phase 8 — Production Hardening

- Security audit
- Performance testing
- Load testing
- Database optimization
- API rate limiting
- Logging
- Monitoring
- Backup strategy
- Deployment
- CI/CD verification