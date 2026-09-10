# ADR-0006: Multi-tenancy mechanism

- Status: Accepted
- Date: 2026-09-09

## Context

`CLAUDE.md` and `docs/SECURITY.md` make tenant isolation a non-negotiable,
explicitly-tested requirement. A `Company` is the tenant. Users belong to many
companies; exactly one is "active" per user at a time
(`users.current_company_id`). The backend must never trust a client-supplied
`company_id`, and every tenant-owned query must be scoped to the active company.

Options considered:

1. **Separate database / schema per tenant** — strongest isolation, heavy ops
   burden, painful cross-tenant reporting, overkill for this product.
2. **A tenancy package** (e.g. `stancl/tenancy`) — powerful, but built around
   database-per-tenant and domain identification; more machinery than we need.
3. **Row-level scoping with a global Eloquent scope + request-scoped tenant
   context** — single database, `company_id` column on tenant tables.

## Decision

Option 3, hand-rolled, three pieces:

### `App\Support\Tenancy\CompanyContext`

A request-scoped singleton holding the active `Company`. `id()` **throws**
`TenantContextMissingException` when unset — a missing context is a bug, never a
silent "all tenants" query.

### `SetActiveCompany` middleware

Runs after `auth`. Resolves `auth()->user()->current_company_id`, verifies a
matching **non-soft-deleted** `company_user` row, and binds `CompanyContext`.

- No `current_company_id`, or it points at a company the user is no longer a
  member of → clear it and return `409 { "message": "...", "code":
  "no_active_company" }` so the SPA shows the company switcher.
- Applied to the tenant-scoped route group only. Auth, `/me`, `/companies`
  (list), and company-switch routes do **not** require an active company.

### `BelongsToCompany` trait

For every tenant-owned model:

- adds a global scope `where company_id = CompanyContext::id()`;
- a `creating` hook that forces `company_id` from the context (any client value
  is ignored);
- `company()` relation;
- `Model::withoutCompanyScope(Closure)` — the **only** sanctioned bypass, for
  seeders and system jobs. Grep-auditable.

`company_id` is never in `$fillable`.

## Consequences

- One database; straightforward cross-tenant reporting for platform admins later
  (explicit `withoutCompanyScope`).
- Isolation is enforced in one place and covered by a reusable test helper
  (`actingInCompany()`), plus a mandatory cross-tenant test per endpoint.
- A route that forgets `SetActiveCompany` fails loudly (exception) rather than
  leaking data.
- Background jobs that touch tenant data must set the context explicitly or use
  `withoutCompanyScope` — documented in the job base class when we add one.
