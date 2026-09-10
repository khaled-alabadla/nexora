# Nexora — Security Requirements

## 1. Authentication

Use Laravel Sanctum in **SPA (cookie session) mode** for the first-party React
app — see [ADR-0004](adr/0004-authentication-transport.md).

- `HttpOnly` + `Secure` + `SameSite=Lax` session cookie; the credential is never
  readable by JavaScript.
- CSRF protection: `/sanctum/csrf-cookie` + `X-XSRF-TOKEN` header on mutating
  requests (`EnsureFrontendRequestsAreStateful` via `statefulApi()`).
- `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, and `CORS_ALLOWED_ORIGINS` must
  list the exact frontend origin(s) in every environment.
- Personal-access (Bearer) tokens remain available for future external/API
  clients; not used by the SPA.

Requirements (all live as of Phase 1.1):

- Password hashing — `bcrypt` via the `hashed` cast; `Password::defaults()`
  (min 8) + confirmation on registration and reset.
- Registration is transactional: user + first company + Owner membership commit
  together or not at all.
- Login — session ID regenerated on success; 5 failed attempts per email+IP
  trigger a timed lockout (`LoginRequest`).
- Logout — `Auth::logout()` + `session()->invalidate()` + token regeneration.
- Password reset — Laravel broker (single-use token, 60-min expiry); the
  "forgot" endpoint always returns the same message, so it never reveals
  whether an address is registered.
- Email verification — signed, expiring URL checked against `sha1(email)`; the
  route trusts the signature, never an auth guard. Resend is throttled.
- Invitation tokens — 48-char random, stored **only as a sha256 hash**; the
  plaintext is emailed once. Acceptance requires the signed-in user to own the
  invited address (`hash_equals`), and enforces single-use + expiry.

Phase 0 configured Sanctum, CORS, and CSRF middleware; the endpoints landed in
Phase 1 (`Modules\Identity`).

---

## 2. Authorization

Authorization always happens server-side. Hiding a button in React is NOT
security — the SPA's `permissions` list is a UX hint only.

Phase 1 implementation:

- **System roles** (`roles` table, seeded) with a numeric `level`. One role per
  user per company (`company_user.role_id`).
- **Permission catalogue** in `App\Support\Authorization\Permissions`; role →
  permission grants in `role_permission`. Owner implicitly holds every
  permission (`Gate::before` + `Role::isOwner()`).
- **`permission:<slug>` route middleware** (`EnsurePermission`) on every mutating
  company route; a matching `Gate::define` per slug for controller/policy use.
- Checks always resolve against the caller's **active** company, never the
  highest role they hold elsewhere (covered by `TenantIsolationTest`).
- The Owner role can never be granted through the API (invite / role-update
  reject it); a company can never lose its last Owner.

---

## 3. Multi-Tenant Isolation

Tenant isolation is a critical security requirement — see
[ADR-0006](adr/0006-tenancy-mechanism.md).

- `App\Support\Tenancy\CompanyContext` — request-scoped singleton holding the
  active company. `id()` **throws** when unset; a missing context is a bug,
  never a silent "all tenants" query.
- `SetActiveCompany` middleware resolves `users.current_company_id`, re-verifies
  a live membership and an `active` company on every request, and binds the
  context. No/stale pointer → `409 no_active_company` (the pointer is cleared).
- `BelongsToCompany` trait — global `where company_id = <active>` scope, a
  `creating` hook that forces `company_id` from the context (client values are
  ignored), and `Model::withoutCompanyScope(Closure)` as the **only** sanctioned,
  grep-auditable bypass (seeders, and invitation acceptance which runs with no
  active company).
- `company_id` is never in `$fillable`. The API never trusts a client
  `company_id` / `user_id` / `role_id`.
- Route parameters for members and invitations are resolved through
  company-scoped lookups, so a foreign id returns `404`, not `403` (no
  existence disclosure). Switching the active company to one you don't belong
  to returns `404`.
- **Automated tests**: `modules/Companies/Tests/Feature/TenantIsolationTest.php`
  (cross-tenant reads/writes, per-company permission evaluation, mid-session
  membership revocation) and `Tests/Unit/BelongsToCompanyScopeTest.php` (the
  scope mechanism itself).

---

## 4. Input Validation

All user input must be validated.

Use Laravel Form Requests or equivalent validation.

Never trust:

- request data
- IDs
- prices
- totals
- permissions
- company IDs

---

## 5. SQL Injection

Use Laravel Eloquent / Query Builder safely.

Never concatenate untrusted user input into raw SQL.

Raw queries require careful parameter binding.

---

## 6. Mass Assignment

Use:

- $fillable
- $guarded

Never allow users to mass assign sensitive fields.

Examples:

- company_id
- user_id
- created_by
- role
- permissions

---

## 7. Financial Security

Financial operations must:

- use transactions
- validate amounts
- prevent negative invalid states
- prevent overpayment
- prevent duplicate payment processing
- maintain accounting balance

Money must use DECIMAL.

---

## 8. Inventory Security

Inventory operations must:

- use transactions
- prevent unauthorized modifications
- prevent negative stock where business rules prohibit it
- prevent cross-tenant access
- record audit information

Concurrent operations must be handled safely.

---

## 9. File Upload Security

Uploaded files must be:

- validated
- restricted by type
- restricted by size
- stored safely
- inaccessible directly when private

Private documents should use controlled/signed access.

---

## 10. Rate Limiting

Apply rate limits to:

- login
- password reset
- sensitive APIs
- expensive reports

---

## 11. Audit Logging

Important actions must be logged.

Audit logs should include:

- user
- company
- action
- entity
- entity_id
- before
- after
- IP
- user agent
- timestamp

Audit logs must also respect tenant isolation.

---

## 12. CORS

CORS must be explicitly configured.

Do not allow unrestricted origins in production.

---

## 13. Secrets

Secrets must never be committed to Git.

Use environment variables for:

- database credentials
- API keys
- application secrets
- mail credentials
- storage credentials

---

## 14. Security Testing

Tests must cover:

- unauthorized access
- role restrictions
- permission restrictions
- tenant isolation
- IDOR
- validation
- authentication
- financial integrity
- inventory integrity