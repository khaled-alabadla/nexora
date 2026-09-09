# Phase 0 — Foundation (completed 2026-09-09)

## Scope

Project infrastructure only. **No business functionality.**

## What was built

### Repository

- Monorepo: `backend/`, `frontend/`, `docker/`, `docs/` (+ `adr/`),
  `.github/workflows/`
- `Makefile` command surface, `.editorconfig`, `.gitattributes` (LF), root and
  per-package `.gitignore` / `.env.example`
- ADRs 0001–0005

### Backend — `backend/` (Laravel 12.69, PHP 8.4)

- **Module system** (ADR-0002): `Modules\` PSR-4 →
  `backend/modules/<Name>/`; `App\Support\Modules\ModuleServiceProvider` base;
  `php artisan make:module` generator; explicit registration in
  `bootstrap/providers.php`; 13 empty modules (Identity … Audit)
- **Thin API layer**: `/api/v1` prefix; `App\Http\Responses\ApiResponse`
  (`{data,message}` / `+meta` / 204); `ForceJsonResponse` middleware; JSON
  exception rendering; `config/nexora.php`
- **Health**: `GET /api/v1/health` → datastore probes, 200 / 503
- **Auth scaffolding** (ADR-0004): Sanctum installed, `statefulApi()`,
  `config/sanctum.php`, `config/cors.php` (credentialed allow-list). No auth
  endpoints yet.
- **Quality gates**: Pest 3 on MySQL `nexora_test` (ADR-0005); Larastan level 6;
  Pint (Laravel preset + strict types)

### Frontend — `frontend/` (React 19, Vite, TypeScript strict)

- TailwindCSS v4, shadcn/ui configured (`components.json`, `cn()`), lucide-react
- TanStack Query bootstrap; Zustand UI store (guarded persistence)
- `src/lib/api.ts` — typed client: credentials, CSRF cookie priming,
  `X-XSRF-TOKEN` on writes, `ApiError`, envelope unwrap
- `features/health` — `useHealth()` + `HealthCard` reading the backend
- ESLint 10 flat (type-checked) + Prettier; Vitest + Testing Library

### Infrastructure — `docker-compose.yml`

`app` (php-fpm 8.4), `nginx`, `queue`, `scheduler`, `mysql` (8.0), `redis` (7),
`node` (22, Vite), `mailpit`. Named volumes for `vendor/` and `node_modules/`.

### CI — `.github/workflows/ci.yml`

`backend` (pint / phpstan / pest `--min=60`), `frontend` (eslint / tsc /
prettier / vitest coverage / build), `security` (gitleaks / composer audit /
npm audit). Runs on push to `main`/`develop` and every PR.

## Verification (all run locally / in-container)

| Gate | Result |
|---|---|
| `pint --test` | PASS (64 files) |
| `phpstan analyse` (L6) | PASS (0 errors) |
| `pest` | 17 passed / 126 assertions |
| backend coverage | 89.3 % (floor 60 %) |
| `curl :8000/api/v1/health` | `{"data":{"status":"ok","database":true,"cache":true},"message":"OK"}` |
| frontend `eslint` / `tsc` / `prettier --check` | PASS |
| frontend `vitest` | 11 passed |
| frontend coverage | 95.9 % stmts (thresholds 80/80/75/65) |
| frontend `vite build` | PASS (js 257 kB / 81 kB gzip) |
| `docker compose` stack | app, nginx, mysql, redis, queue, scheduler, mailpit healthy/running |

`git push` / PR / remote CI were **not** available in the build environment
(classifier-blocked; no `gh`/token). History is local on `main` / `develop`;
CI runs on first push.

## Deviations from the plan

| Planned | Actual | Why |
|---|---|---|
| `mysql:8.4` | `mysql:8.0` (`MYSQL_IMAGE` overridable) | 8.4 image would not pull reliably; 8.0 satisfies "MySQL 8+", identical behaviour for our concerns |
| ESLint (unspecified major) | ESLint 10 flat config | current major in the environment |
| Node 25 host | pinned Node 22 (`.nvmrc`, containers, CI) | 25 is non-LTS; 22 is LTS |
| 14 micro-branches | 5 feature branches | no business logic in this phase; one phase-level GRILL-ME already done |

## Carried forward

See `docs/ROADMAP.md` "Deferred to later phases".
