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

# Phase 0 — Foundation

Goal:

Prepare the project infrastructure.

Tasks:

- Repository structure
- Laravel setup
- React setup
- TypeScript
- Docker
- MySQL
- Redis
- Queue worker
- Scheduler
- Testing
- Pest
- PHPStan
- Pint
- CI/CD
- Documentation structure
- CLAUDE.md

No business features.

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