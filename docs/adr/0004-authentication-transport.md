# ADR-0004: Authentication transport — Sanctum SPA cookie session

- Status: Accepted
- Date: 2026-09-09

## Context

Nexora has a decoupled React SPA (first-party) that talks to `/api/v1`. An early
draft of `docs/API.md` showed `Authorization: Bearer <token>`. We must choose how
the first-party SPA authenticates:

1. **Sanctum personal-access tokens (Bearer)** — token returned at login, stored
   in JS-reachable storage, sent as a header.
2. **Sanctum SPA authentication (cookie session)** — `HttpOnly`, `Secure`,
   `SameSite` cookie; CSRF-protected; no token in JS.

`docs/SECURITY.md` requires strong session/token security and treats XSS as a
first-class threat. Option 1 exposes the credential to any successful XSS.

## Decision

The first-party SPA uses **Sanctum SPA cookie-session authentication**:

- `HttpOnly` + `Secure` + `SameSite=Lax` session cookie.
- CSRF protection via Sanctum's `/sanctum/csrf-cookie` + `X-XSRF-TOKEN`.
- `config/cors.php` restricted to the known frontend origin(s);
  `supports_credentials = true`.
- `SANCTUM_STATEFUL_DOMAINS` lists the frontend host(s).

**Personal-access tokens remain available** (Sanctum supports both simultaneously)
for a future external/public API and machine-to-machine integrations. Those are
out of scope until a concrete requirement exists.

`docs/API.md` is updated: first-party auth is cookie-based; the `Authorization:
Bearer` header is reserved for future token clients.

## Consequences

- The login credential is never readable by JavaScript — XSS cannot exfiltrate it.
- The SPA and API must be served from the same registrable domain (or configured
  stateful domains) in every environment; CORS + `SANCTUM_STATEFUL_DOMAINS` +
  `SESSION_DOMAIN` must be set per environment.
- The frontend HTTP client must call the CSRF-cookie endpoint before the first
  mutating request and send `withCredentials`.
- Auth endpoints themselves are implemented in Phase 1; Phase 0 only installs and
  configures Sanctum, CORS, and the CSRF middleware.
