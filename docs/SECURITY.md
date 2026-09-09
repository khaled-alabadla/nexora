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

Requirements:

- Secure authentication (implemented in Phase 1)
- Password hashing (bcrypt, cost 12)
- Password reset
- Email verification
- Session/token security
- Logout / session invalidation

Phase 0 configures Sanctum, CORS, and CSRF middleware only; auth endpoints are
Phase 1.

---

## 2. Authorization

Authorization must always happen server-side.

Use:

- Policies
- Gates
- Permissions
- Roles

Never rely on frontend authorization.

Hiding a button in React is NOT security.

---

## 3. Multi-Tenant Isolation

Tenant isolation is a critical security requirement.

Users must never access data belonging to another company.

The backend must derive the active company from authenticated
context.

Never trust:

company_id

from request input.

Every tenant-owned query must be properly scoped.

Tenant isolation must have automated tests.

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