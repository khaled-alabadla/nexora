# ADR-0005: Run the test suite against MySQL, not SQLite

- Status: Accepted
- Date: 2026-09-09
- Note (2026-09-09): the dev/CI MySQL image is pinned to **`mysql:8.0`** for now.
  The `mysql:8.4` registry pull was not completing reliably in the build
  environment. `docs/DATABASE.md` requires "MySQL 8+", which 8.0 satisfies, and
  every behaviour this ADR depends on (DECIMAL semantics, InnoDB row locking,
  FK enforcement, strict `sql_mode`, JSON/window functions) is identical in
  8.0 and 8.4. The image is overridable via `MYSQL_IMAGE` in the root `.env`;
  bump to `mysql:8.4` once available. Tracked as a Phase 0 follow-up.

## Context

Laravel's default test setup uses an in-memory SQLite database for speed. Nexora's
core domains — inventory ledger and double-entry accounting — depend on behaviour
that differs between SQLite and MySQL:

- `DECIMAL` precision and arithmetic semantics.
- Pessimistic locking (`SELECT ... FOR UPDATE`, `SKIP LOCKED`) used for
  concurrent stock updates.
- Strict `sql_mode`, `NO_ZERO_DATE`, `ONLY_FULL_GROUP_BY`.
- Foreign-key enforcement nuances and error codes.
- JSON functions, generated columns, window functions used in reporting.
- Deadlock behaviour and transaction isolation levels.

Testing against SQLite would give false confidence and diverge from production in
exactly the areas where correctness matters most (money, stock).

## Decision

All automated tests run against **MySQL 8.4**, the same engine as production.

- CI provisions a `mysql:8.4` service.
- Locally, the `mysql` compose service hosts a dedicated `nexora_test` schema
  (separate from `nexora`), created by `make setup`.
- `backend/phpunit.xml` sets `DB_CONNECTION=mysql`, `DB_DATABASE=nexora_test`.
- `RefreshDatabase` (transactional) is the default; tests that exercise real
  concurrency/locking use `DatabaseTransactions` off or dedicated connections as
  needed.

Trade-off accepted: the suite is slower than in-memory SQLite. Mitigations —
`--parallel` with per-process test schemas, and running the smallest relevant
subset during development.

## Consequences

- Tests exercise the real SQL dialect, locking, and constraint behaviour.
- CI and local runs need a running MySQL (already required by the compose stack).
- Test bootstrapping must create/clean `nexora_test`; documented in
  `docs/TESTING.md`.
