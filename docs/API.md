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

`per_page` is clamped server-side (default 20, max 100 — enforced per endpoint
from Phase 2).

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

## 10. Product endpoints

> Implemented in Phase 2.

```
GET    /api/v1/products
POST   /api/v1/products
GET    /api/v1/products/{id}
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}
```

Authorization is required for every endpoint.

---

## 11. Security

The API never trusts client-provided:

- `company_id`
- `user_id`
- `created_by`
- invoice totals
- calculated prices
- permissions

The backend derives these from authenticated context and business rules.

---

## 12. Rate limiting

> Applied from Phase 1 (auth) / Phase 8 (hardening).

Sensitive endpoints (login, password reset, token auth, expensive reports) get
dedicated throttles via `RateLimiter`.

---

## 13. CORS

`config/cors.php` — credentialed, with an explicit origin allow-list
(`CORS_ALLOWED_ORIGINS`, default `http://localhost:5173`). Never `*` with
credentials.
