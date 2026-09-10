# Nexora — Architecture

## 1. Architecture Style

Nexora uses a Modular Monolith architecture.

We do NOT use microservices.

The application is divided into business modules while
remaining inside a single deployable application.

---

## 2. Backend Stack

- PHP 8.4+ — runs in Docker; the host PHP is not supported (see
  [ADR-0003](adr/0003-docker-dev-environment.md))
- Laravel 12
- MySQL 8+ — image pinned to `mysql:8.0`, overridable via `MYSQL_IMAGE`
  (see [ADR-0005](adr/0005-test-database-mysql.md))
- Redis 7 — cache, queue, rate limiting
- Laravel Queues (`queue` container)
- Laravel Scheduler (`scheduler` container)
- Laravel Events
- Laravel Notifications
- Laravel Sanctum — SPA cookie sessions (see
  [ADR-0004](adr/0004-authentication-transport.md))

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

Business capabilities live in `backend/modules/<Name>/` under the `Modules\`
PSR-4 namespace. Each module has a `<Name>ServiceProvider` (extending
`App\Support\Modules\ModuleServiceProvider`) registered explicitly in
`backend/bootstrap/providers.php` — no auto-discovery. The provider loads that
module's `Routes/api.php` (under `/api/v1`, `api` middleware), migrations, and
translations. Scaffold with `php artisan make:module <Name>`. Full rationale and
directory layout: [ADR-0002](adr/0002-modular-monolith-layout.md).

Modules (⬤ = has functionality as of Phase 1.1):

- ⬤ Identity — registration, login/logout, password reset, email verification
- ⬤ Companies — companies, memberships, active company, roles, permissions,
  invitations, and the tenancy middleware/context
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

A Company represents a tenant; users may belong to many, with exactly one
**active** at a time (`users.current_company_id`). Implemented in Phase 1 per
[ADR-0006](adr/0006-tenancy-mechanism.md):

- `App\Support\Tenancy\CompanyContext` — request-scoped singleton; `id()` throws
  when unbound.
- `SetActiveCompany` middleware — re-verifies membership + company status per
  request and binds the context; `409 no_active_company` otherwise.
- `App\Support\Tenancy\BelongsToCompany` trait — global `company_id` scope +
  forced `company_id` on create; `withoutCompanyScope()` is the only bypass.

The backend never trusts a client-supplied `company_id`. All tenant-owned
queries are scoped to the active company, and this is covered by a dedicated
cross-tenant test suite.

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
- consistent responses (`App\Http\Responses\ApiResponse` envelopes)
- consistent error handling (`{ message, errors }`, always JSON for `/api/*`)

See [`docs/API.md`](API.md) for the response/error contract.

---

## 10. Local environment

`docker-compose.yml` runs the whole stack: `app` (php-fpm), `nginx`, `queue`,
`scheduler`, `mysql`, `redis`, `node` (Vite), `mailpit`. Day-to-day commands are
wrapped in the root `Makefile` (`make up`, `make setup`, `make check`,
`make check-frontend`). Node/vendor trees sit on named volumes for bind-mount
performance. See [`docs/DEVELOPMENT.md`](DEVELOPMENT.md).

---

## 11. Quality gates

- **Backend:** Pint (style), Larastan/PHPStan **level 8** (raised in Phase 1),
  Pest on MySQL (`nexora_test`), line-coverage floor 85% (raised in Phase 1).
- **Frontend:** ESLint (type-checked flat config), `tsc --noEmit`, Prettier,
  Vitest (coverage thresholds 80/80/75/65).
- **CI:** `.github/workflows/ci.yml` runs both plus gitleaks and dependency
  audits on every push and PR.