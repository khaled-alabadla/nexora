# Nexora — Architecture

## 1. Architecture Style

Nexora uses a Modular Monolith architecture.

We do NOT use microservices.

The application is divided into business modules while
remaining inside a single deployable application.

---

## 2. Backend Stack

- PHP 8.4+
- Laravel 12
- MySQL 8+
- Redis
- Laravel Queues
- Laravel Scheduler
- Laravel Events
- Laravel Notifications
- Laravel Sanctum

---

## 3. Frontend Stack

- React
- TypeScript
- Vite
- TailwindCSS
- shadcn/ui
- TanStack Query
- Zustand

---

## 4. Modules

backend/modules/

- Identity
- Companies
- Customers
- Suppliers
- Products
- Inventory
- Sales
- Purchases
- Accounting
- Expenses
- Reports
- Notifications
- Audit

---

## 5. Multi-Tenancy

A Company represents a tenant.

Users may belong to multiple companies.

The active company is resolved from the authenticated
user context.

The backend MUST NOT trust company_id supplied by the client.

All tenant-owned queries must be scoped to the active company.

---

## 6. Application Layers

Controllers
    ↓
Application Services
    ↓
Domain / Business Logic
    ↓
Repositories / Eloquent
    ↓
Database

Controllers should remain thin.

Business logic must not be implemented directly inside controllers.

---

## 7. Transactions

Financial and inventory operations must use database transactions.

Examples:

- Creating an invoice
- Processing a payment
- Updating inventory
- Warehouse transfers
- Posting journal entries

---

## 8. Background Processing

Redis is used for:

- queues
- caching
- rate limiting

Long-running operations should be queued.

Examples:

- report exports
- emails
- notifications
- large data processing

---

## 9. API

All APIs use:

/api/v1/

APIs must provide:

- authentication
- authorization
- validation
- pagination
- filtering
- sorting
- consistent responses
- consistent error handling