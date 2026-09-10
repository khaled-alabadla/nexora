# Phase 1 — Identity & Multi-Tenancy

## Slice 1.1 — Identity Foundation (completed 2026-09-10)

Branch `feature/1.1-identity-foundation`. Scope: authentication, companies &
memberships, roles & permissions, tenant isolation, and the SPA that drives
them.

## GRILL-ME decisions (ratified)

| Question | Decision |
|---|---|
| Active company — FK vs session | `users.current_company_id` FK, membership re-checked every request by `SetActiveCompany`. |
| Roles — global vs per-company | System-defined only in Phase 1 (8 roles, seeded). Per-company custom roles deferred. |
| Registration | Creates user + first company + Owner membership in one DB transaction. |
| Tenant scope mechanism | `BelongsToCompany` trait (global scope + forced `company_id`) + `CompanyContext` singleton + `SetActiveCompany` middleware. → ADR-0006 |
| `company_user.role_id` | One role per user per company. Confirmed. |
| Money precision | `DECIMAL(18,2)` amounts / `DECIMAL(18,4)` rates. Ratified; mirrored in `config/nexora.php`. |
| PHPStan level | Raised 6 → **8** (Phase 1 exit gate). |

## What was built

### Backend — `Modules\Identity`

- `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`,
  `GET /auth/me`
- `POST /auth/password/forgot`, `POST /auth/password/reset`
- `GET /auth/email/verify/{id}/{hash}` (signed),
  `POST /auth/email/verification-notification`
- `RegisterUser` action (transaction), `SessionPayload` assembler,
  `LoginRequest` with per-email+IP throttling, notification links pointed at the
  SPA (`IdentityServiceProvider`)

### Backend — `Modules\Companies`

- Models: `Company` (soft-deletes), `Role`, `Permission`, `CompanyUser` (pivot),
  `CompanyInvitation` (tenant-scoped, token hashed at rest)
- Services: `CompanyProvisioner`, `CompanyMembershipService`,
  `CompanyInvitationService`
- Endpoints: list/create companies, switch active company, show/update company,
  list members, change role, remove member, list/send/revoke invitations,
  accept invitation, `GET /roles`
- `RolesAndPermissionsSeeder` run from a migration (idempotent reference data)
- `SetActiveCompany` + `EnsurePermission` middleware; a `Gate` per permission

### Shared — `App\Support`

- `Tenancy\CompanyContext`, `Tenancy\BelongsToCompany`,
  `Tenancy\TenantContextMissingException`
- `Authorization\Permissions` (catalogue), `Authorization\GrantedPermissions`

### Frontend — `frontend/src`

- `lib/api.ts` extended (`put`/`patch`/`delete`, 204 handling); `lib/forms.ts`
- `features/auth` — session hooks, `RequireAuth`, and the login / register /
  forgot-password / reset-password pages (react-router-dom)
- `features/companies` — company switcher, create-company card, members panel
  (invite / role / remove / revoke, each gated on the caller's permissions),
  invitation-acceptance page
- `pages/DashboardPage`, `components/ui.tsx`, verify-email banner

## Verification (in-container)

| Gate | Result |
|---|---|
| `pint --test` | PASS (135 files) |
| `phpstan analyse` (**L8**) | PASS (0 errors) |
| `pest` | 80 passed / 299 assertions |
| backend coverage | 95.8 % (CI floor raised 60 → 85 %) |
| frontend `eslint` / `tsc` / `prettier --check` | PASS |
| frontend `vitest` | 51 passed (16 files) |
| frontend coverage | 95 % stmts / 82 % branch (thresholds 80/80/75/65) |
| frontend `vite build` | PASS (js 319 kB / 99 kB gzip) |
| `composer audit` / `npm audit` | 0 advisories |

`git push` / remote CI remain unavailable in this environment; history is local
on `develop` after the phase merge. CI runs on first push.

## Security review

Reviewed the branch diff against `docs/SECURITY.md`. **0 critical / 0 high.**

Confirmed:

- Tenant isolation enforced in one place (`CompanyContext` throws when unset;
  global scope + forced `company_id`); cross-tenant reads and writes are
  covered by `TenantIsolationTest`. Foreign ids return `404`, not `403`.
- `company_id` / `role_id` / `current_company_id` / `email_verified_at` are not
  mass-assignable; `Company.status` is not client-settable.
- Invitation tokens stored only as sha256 hashes; acceptance is email-bound with
  `hash_equals`, single-use, and expiring.
- Login lockout, session regeneration on login, session invalidation on logout,
  no user enumeration on password reset, signed + hash-checked verification.
- Owner role cannot be granted via the API; the last Owner cannot be removed or
  demoted.

Low / informational (tracked, not blocking):

- Login throttle is keyed on email+IP (Breeze default); an attacker rotating
  IPs bypasses the per-account limit. Add a per-account counter in Phase 8
  hardening.
- `SetActiveCompany` clears a stale `current_company_id` on a `GET` (a write on
  a read). Intentional self-healing; acceptable.

## Deviations from the plan

| Planned | Actual | Why |
|---|---|---|
| — | Added `react-router-dom` | A multi-page auth flow (verify-email, reset-password are URL-driven) needs real routing; justified per CLAUDE.md. |
| Test `SESSION_DRIVER=array` | `database` | The array driver does not persist the session cookie across requests, so the real login→request→logout flow could not be exercised. |
| One PR per sub-feature | One squash-style feature branch, 3 commits (backend / frontend / docs) then `--no-ff` merge | Autonomous local-only workflow; gates are the working review. |

## Carried forward (Phase 1.2+ / later)

- Per-company custom roles
- Team-facing invitation management UI polish, pending-invite resend
- Per-account login throttle (Phase 8)
- `journal_entry_lines.company_id` denormalisation decision (Phase 5)
