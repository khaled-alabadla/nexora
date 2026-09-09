# Nexora — Testing Strategy

## Test runner

- **Pest 3** (on PHPUnit 11) for the backend.
- **Vitest** + Testing Library for the frontend.

## Database — MySQL, not SQLite

All backend tests run against **MySQL 8.4**, the production engine. The rationale
is in [ADR-0005](adr/0005-test-database-mysql.md): Nexora's correctness-critical
domains (inventory ledger, double-entry accounting) depend on `DECIMAL`
semantics, row-level locking (`SELECT … FOR UPDATE`), strict `sql_mode`, and
foreign-key enforcement that SQLite does not reproduce.

- Test schema: **`nexora_test`** (created by the `mysql` container's init script
  and by `make setup`).
- `phpunit.xml` pins `DB_CONNECTION=mysql`, `DB_DATABASE=nexora_test`.
- Default isolation: `RefreshDatabase` (wraps each test in a transaction).
- Concurrency/locking tests opt out of the wrapping transaction and manage their
  own connections (added in Phase 2+).

## Layout

| Location | Suite | Contents |
|---|---|---|
| `backend/tests/Unit` | Unit | Pure logic, no framework boot |
| `backend/tests/Feature` | Feature | HTTP, DB, container |
| `backend/modules/<M>/Tests/Unit` | Unit | Module unit tests |
| `backend/modules/<M>/Tests/Feature` | Feature | Module HTTP/DB tests |

Module tests are auto-discovered — see `phpunit.xml` and `tests/Pest.php`.

## What every significant feature must test

Per `CLAUDE.md`, business features must cover:

- **Happy path** and **failure paths**
- **Authorization** — each role/permission boundary
- **Tenant isolation** — a user of company A cannot read/write company B's data
  (explicit, per-endpoint)
- **Validation** — rejected input, mass-assignment protection
- **Database integrity** — constraints, uniqueness, FK behaviour
- **Business rules** — invalid state transitions, duplicates, boundaries
- **Concurrency** — where stock or money can race
- **Transactions** — partial-failure rollback

## Commands

```bash
make test               # full suite (Pest, MySQL)
make test-parallel      # parallel run
make check              # lint + analyse + test (backend gate)
make check-frontend     # eslint + tsc + vitest + build (frontend gate)

# Narrow runs during development:
make artisan ARGS="test --filter=HealthEndpoint"
make artisan ARGS="test modules/Sales"
```

## Coverage

CI enforces a **minimum line-coverage floor of 60%** for Phase 0, raised each
phase (target 85%+ for business modules). Coverage is reported by
`php artisan test --coverage`.

## Static analysis & style

Not optional, and run in CI:

- **PHPStan / Larastan** — level 6 (Phase 0); level 8 is a Phase 1 exit gate.
- **Pint** — Laravel preset plus strict types / strict comparisons.
- **ESLint** + **tsc --noEmit** for the frontend.
