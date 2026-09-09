# Nexora — Security Requirements

## 1. Authentication

Use Laravel Sanctum.

Requirements:

- Secure authentication
- Password hashing
- Password reset
- Email verification
- Session/token security
- Logout/revocation where applicable

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