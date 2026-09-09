# anatomy.md

> Auto-maintained by OpenWolf. Last scanned: 2026-09-09T11:22:12.169Z
> Files: 76 tracked | Anatomy hits: 0 | Misses: 0

> Project structure index. Auto-maintained by OpenWolf hooks and daemon.
> Run `openwolf scan` to generate, or wait for the first Claude Code session.
> Status: Pending initial scan

## ./

- `.dockerignore` — Keep the PHP image build context small and secret-free. (~67 tok)
- `.editorconfig` — https://editorconfig.org (~104 tok)
- `.gitattributes` — Normalize line endings: LF in the repo, regardless of host OS. (~220 tok)
- `.gitignore` — Git ignore rules (~336 tok)
- `.gitleaks.toml` — Extends the default gitleaks rule set. (~74 tok)
- `.nvmrc` (~1 tok)
- `AGENTS.md` — OpenWolf (~75 tok)
- `CLAUDE.md` — OpenWolf (~99 tok)
- `docker-compose.yml` — Docker Compose services (~1012 tok)
- `GEMINI.md` — OpenWolf (~75 tok)
- `Makefile` — Nexora — developer command surface. (~1002 tok)
- `README.md` — Project documentation (~819 tok)

## .github/workflows/

- `ci.yml` — CI: CI (~1040 tok)

## backend/

- `.gitignore` — Git ignore rules (~84 tok)
- `composer.json` — PHP package manifest (~782 tok)
- `phpstan.neon` (~92 tok)
- `phpunit.xml` (~432 tok)
- `pint.json` (~169 tok)

## backend/app/Console/Commands/

- `MakeModuleCommand.php` — Scaffolds a business module (see ADR-0002) and registers its service (~1226 tok)

## backend/app/Http/Controllers/

- `HealthController.php` — Liveness / readiness probe. (~356 tok)

## backend/app/Http/Middleware/

- `ForceJsonResponse.php` — Guarantees API clients are treated as JSON consumers even when they omit the (~153 tok)

## backend/app/Http/Responses/

- `ApiResponse.php` — Builds the API's canonical success envelope (see docs/API.md): (~396 tok)

## backend/app/Providers/

- `AppServiceProvider.php` — Fail loudly in non-production on lazy loading, bad mass-assignment, and (~417 tok)

## backend/app/Support/Modules/

- `ModuleServiceProvider.php` — Base provider for every business module (see ADR-0002). (~494 tok)

## backend/bootstrap/

- `app.php` (~317 tok)
- `providers.php` (~269 tok)

## backend/config/

- `cors.php` (~223 tok)
- `nexora.php` (~274 tok)

## backend/routes/

- `api.php` (~152 tok)
- `web.php` (~168 tok)

## backend/tests/

- `Pest.php` — Declares apiUrl (~336 tok)
- `TestCase.php` — Declares TestCase (~45 tok)

## backend/tests/Feature/

- `HealthEndpointTest.php` — Declares Pest (~197 tok)
- `MakeModuleCommandTest.php` — Declares SCRATCH_MODULE (~655 tok)
- `ModuleSystemTest.php` — Declares EXPECTED_MODULES (~575 tok)

## backend/tests/Unit/

- `ApiResponseTest.php` (~310 tok)

## docker/mysql/init/

- `01-create-test-database.sql` — Runs once on first MySQL container start (empty data dir). (~116 tok)

## docker/nginx/

- `default.conf` (~276 tok)

## docker/php/

- `Dockerfile` — Docker container definition (~512 tok)
- `entrypoint.sh` (~168 tok)
- `php.ini` (~117 tok)
- `xdebug.ini` (~78 tok)

## docs/

- `DEVELOPMENT.md` — Nexora — Development Guide (~963 tok)
- `TESTING.md` — Nexora — Testing Strategy (~679 tok)

## docs/adr/

- `0001-record-architecture-decisions.md` — ADR-0001: Record architecture decisions (~255 tok)
- `0002-modular-monolith-layout.md` — ADR-0002: Modular monolith layout with hand-rolled PSR-4 modules (~628 tok)
- `0003-docker-dev-environment.md` — ADR-0003: Full Docker development environment (~416 tok)
- `0004-authentication-transport.md` — ADR-0004: Authentication transport — Sanctum SPA cookie session (~504 tok)
- `0005-test-database-mysql.md` — ADR-0005: Run the test suite against MySQL, not SQLite (~586 tok)

## frontend/

- `.gitignore` — Git ignore rules (~77 tok)
- `.prettierignore` (~16 tok)
- `.prettierrc.json` (~31 tok)
- `components.json` (~122 tok)
- `eslint.config.js` (~370 tok)
- `index.html` — Nexora (~96 tok)
- `package.json` — Node.js package manifest (~440 tok)
- `README.md` — Project documentation (~415 tok)
- `tsconfig.app.json` (~266 tok)
- `tsconfig.node.json` (~171 tok)
- `vite.config.ts` — /*.{ts,tsx}'], (~249 tok)

## frontend/src/

- `App.test.tsx` — fetchMock (~175 tok)
- `App.tsx` — App (~74 tok)
- `index.css` — Styles: 1 rules (~51 tok)
- `main.tsx` — queryClient (~174 tok)
- `vite-env.d.ts` — / <reference types="vite/client" /> (~45 tok)

## frontend/src/features/health/

- `HealthCard.test.tsx` — fetchMock (~345 tok)
- `HealthCard.tsx` — Indicator (~459 tok)
- `useHealth.ts` — Exports HealthStatus, useHealth (~104 tok)

## frontend/src/lib/

- `api.test.ts` — API routes: GET, POST (6 endpoints) (~463 tok)
- `api.ts` — Minimal typed API client for the Nexora backend. (~741 tok)
- `utils.test.ts` — Declares names (~85 tok)
- `utils.ts` — Merge conditional class names, de-duplicating Tailwind utilities. (~71 tok)

## frontend/src/store/

- `ui.test.ts` — Declares raw (~206 tok)
- `ui.ts` — Guarded storage: `localStorage` can be unavailable (private mode, disabled (~353 tok)

## frontend/src/test/

- `setup.ts` (~46 tok)
- `utils.tsx` — Build a minimal JSON `Response` for stubbing `fetch` in tests. (~313 tok)
