# ADR-0005: Run the test suite against MySQL, not SQLite

- Status: Accepted
- Date: 2026-09-09

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
