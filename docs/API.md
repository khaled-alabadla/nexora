# Nexora — API Specification

## 1. Base URL

```
/api/v1
```

Configured in `backend/bootstrap/app.php` (`apiPrefix`) and mirrored in
`config('nexora.api_prefix')`.

---

## 2. Authentication

The first-party SPA authenticates with **Laravel Sanctum SPA (cookie) sessions**
— see [ADR-0004](adr/0004-authentication-transport.md).

Flow:

1. `GET /sanctum/csrf-cookie` once, to set the `XSRF-TOKEN` cookie.
2. Send every request with credentials (cookies).
3. Mutating requests echo the cookie in the `X-XSRF-TOKEN` header.

`Authorization: Bearer <token>` is **reserved for future** external / machine
clients (Sanctum personal-access tokens); it is not used by the SPA.

Session/CSRF configuration lives in `config/sanctum.php`, `config/session.php`,
and `SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN` in the environment.

---

## 3. Response format

**Single resource / object**

```json
{
  "data": { },
  "message": "OK"
}
```

**Collection**

```json
{
  "data": [],
  "message": "OK",
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200,
    "from": 1,
    "to": 20
  }
}
```

Every success response carries `data` and `message`; collections additionally
carry `meta`. Built by `App\Http\Responses\ApiResponse`.

`204 No Content` responses (e.g. deletes) have an empty body.

---

## 4. Errors

Consistent structure, always JSON for `/api/*` (enforced by
`ForceJsonResponse` + the exception handler):

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

`errors` is present only for `422` validation failures. Other failures
(`401`, `403`, `404`, `409`, `429`, `500`) return `{ "message": "..." }`.

---

## 5. Pagination

```
GET /api/v1/products?page=1&per_page=20
```

`per_page` is clamped server-side (default 20, max 100). Live since Phase 2.1
via `App\Support\Http\QueryFilter` + `ApiResponse::paginated()`.

---

## 6. Filtering

```
GET /api/v1/products?status=active
```

---

## 7. Searching

```
GET /api/v1/products?search=laptop
```

---

## 8. Sorting

```
GET /api/v1/products?sort=-created_at
```

Leading `-` = descending. Allowed sort fields are whitelisted per endpoint.

---

## 9. Health

```
GET /api/v1/health        (unauthenticated)
```

```json
{ "data": { "status": "ok", "database": true, "cache": true }, "message": "OK" }
```

Returns `503` with `status: "degraded"` if a datastore is unreachable.

---

## 10. Identity & tenancy endpoints (Phase 1)

All paths are under `/api/v1`. Unless noted, requests use the SPA cookie
session. Bodies and responses use the envelope from §3.

### 10.1 Authentication

| Method & path | Auth | Notes |
|---|---|---|
| `POST /auth/register` | guest | `{ name, email, password, password_confirmation, company_name }`. Creates the user + their first company + the Owner membership in one transaction, signs them in, dispatches the verification email. Returns the session payload (§10.3). Throttled `6/min`. |
| `POST /auth/login` | guest | `{ email, password, remember? }`. Rate-limited 5 failures per email+IP, then a lockout window. Returns the session payload. |
| `POST /auth/logout` | user | Invalidates the session. `204`. |
| `GET  /auth/me` | user | The session payload (§10.3). |
| `POST /auth/password/forgot` | guest | `{ email }`. Always `200` with a neutral message — never discloses whether the address is registered. Throttled `6/min`. |
| `POST /auth/password/reset` | guest | `{ token, email, password, password_confirmation }`. `422` on an invalid/expired token. |
| `GET  /auth/email/verify/{id}/{hash}` | signed URL | Opened from the email; verifies and `302`-redirects to `FRONTEND_URL/login?verified=1`. `403` on a bad signature or hash. |
| `POST /auth/email/verification-notification` | user | Resends the link. Throttled `6/min`. |

### 10.2 Companies, memberships & roles

| Method & path | Permission | Notes |
|---|---|---|
| `GET  /companies` | any member | The caller's companies, each with their role. No active company required. |
| `POST /companies` | verified user | `{ name }`. Caller becomes Owner. |
| `PUT  /companies/{id}/active` | membership | Switches the active company. `404` if the caller is not a member, `422` if the company is suspended. |
| `GET  /roles` | user | System role catalogue for pickers. |
| `POST /invitations/{token}/accept` | user | Accepts an emailed invitation; the signed-in user must own the invited address (`422` otherwise). No active company required. |
| `GET  /company` | active company | The active company. |
| `PUT  /company` | `company.update` | `{ name }`. |
| `GET  /company/members` | `member.view` | |
| `PATCH  /company/members/{userId}` | `member.role.update` | `{ role }` (slug; `owner` rejected). Cannot demote the last owner. `404` for a non-member. |
| `DELETE /company/members/{userId}` | `member.remove` | Cannot remove the last owner. `204`. |
| `GET  /company/invitations` | `member.view` | Pending/'expired'/'accepted' invitations for the active company. |
| `POST /company/invitations` | `member.invite` | `{ email, role }`. Token is hashed at rest; the plaintext is only emailed. |
| `DELETE /company/invitations/{id}` | `member.invite` | `204`. |

Routes without an active company bound respond `409 { "code": "no_active_company" }`
so the SPA shows the company switcher. A stale `current_company_id` is cleared.

### 10.3 Session payload

```json
{
  "data": {
    "user": { "id": 1, "name": "Ada", "email": "ada@example.com", "email_verified": false },
    "current_company": { "id": 7, "name": "…", "slug": "…", "status": "active", "role": { "slug": "owner", "name": "Owner", "level": 100 } },
    "companies": [ { "id": 7, "name": "…", "slug": "…", "status": "active", "role": { … } } ],
    "permissions": ["company.update", "member.view", "member.invite", "member.role.update", "member.remove"]
  },
  "message": "OK"
}
```

`permissions` are the slugs effective for the caller **in the active company**
(Owner holds all). Empty when no company is active.

---

## 11. Products & categories (Phase 2.1)

All under `/api/v1`, `auth:sanctum` + `active-company`. Body/response fields
match `docs/DATABASE.md` §5.

| Method & path | Permission | Notes |
|---|---|---|
| `GET  /products` | `product.view` | Paginated (§5). Filters: `?status=`, `?category_id=`. Search: `?search=` over sku/name/barcode. Sort: `?sort=name\|sku\|created_at` (`-` for desc). |
| `POST /products` | `product.create` | `{ category_id?, sku, name, description?, barcode?, unit?, cost_price?, selling_price?, tax_rate?, minimum_stock?, status? }`. `sku`/`barcode` unique per company. |
| `GET  /products/{id}` | `product.view` | |
| `PUT  /products/{id}` | `product.update` | Same fields, all optional. |
| `DELETE /products/{id}` | `product.delete` | Soft delete — the SKU/barcode stay reserved. `204`. |
| `GET  /categories` | `category.manage` | Not paginated. |
| `POST /categories` | `category.manage` | `{ name, parent_id?, status? }`. `parent_id` must belong to the same company and cannot be the category itself. |
| `GET  /categories/{id}` | `category.manage` | |
| `PUT  /categories/{id}` | `category.manage` | |
| `DELETE /categories/{id}` | `category.manage` | Hard delete; children and products are re-parented to `null`. `204`. |

Every id is resolved through Laravel route-model binding, tenant-scoped by
`BelongsToCompany`'s global scope (`active-company` is in the middleware
priority list, ahead of `SubstituteBindings` — see
`docs/PHASE-2-PLAN.md` §7.6) — a foreign company's id always `404`s.

---

## 12. Security

The API never trusts client-provided:

- `company_id`
- `user_id`
- `created_by`
- invoice totals
- calculated prices
- permissions

The backend derives these from authenticated context and business rules.

---

## 13. Rate limiting

Live since Phase 1; broader hardening in Phase 8.

- `login` — 5 failed attempts per email+IP, then a timed lockout
  (`LoginRequest`).
- `register`, `password/forgot`, `password/reset`, `email/verify`,
  `email/verification-notification` — `throttle:6,1` (6 requests/minute).
- Everything else inherits the default `api` throttle.

---

## 14. CORS

`config/cors.php` — credentialed, with an explicit origin allow-list
(`CORS_ALLOWED_ORIGINS`, default `http://localhost:5173`). Never `*` with
credentials.
