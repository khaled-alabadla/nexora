# ADR-0007: Stock as a maintained projection, and how it stays consistent under concurrency

- Status: Accepted
- Date: 2026-09-14

## Context

`CLAUDE.md` requires inventory operations to be auditable and accounting-grade
correct under concurrency. `docs/PHASE-2-PLAN.md` §7.1/§7.2 (ratified
GRILL-ME) already settled the headline shape: `inventory_movements` is an
append-only, signed-quantity ledger and the source of truth; `stock` is a
maintained projection of `SUM(quantity)` per (company, product, warehouse),
kept in sync inside the same transaction as each movement. What remained open
going into implementation was *how* to make "kept in sync" hold under real
concurrent writers, having just been burned by this exact class of bug in
Phase 2.2 (`WarehouseService`, see `.wolf/cerebrum.md` Do-Not-Repeat).

Two failure modes to design against:

1. **Lost updates** on an *existing* stock row: two movements for the same
   (product, warehouse) committing concurrently must both land, never one
   silently overwriting the other.
2. **Duplicate/missing rows** on the *first* movement for a (product,
   warehouse) pair that has no stock row yet — a bare `lockForUpdate()` query
   matching zero rows only gap-locks under MySQL's REPEATABLE READ isolation
   level (never explicitly pinned anywhere in this app), so under READ
   COMMITTED it takes no lock at all and two concurrent "first" movements can
   both decide they're first.

Phase 2.2 hit exactly problem 2 for `warehouses.is_default` and fixed it with
a MySQL named lock (`GET_LOCK`/`RELEASE_LOCK`), because that invariant has no
real database constraint backing it — MySQL has no partial unique index.

## Decision

**`stock` gets a real unique constraint: `(company_id, product_id,
warehouse_id)`.** Unlike `is_default`, this invariant — "at most one stock
row per pair" — is expressible as an ordinary unique index. That changes the
correct fix:

- **Existing row**: `InventoryLedger::record()` takes a plain
  `SELECT ... FOR UPDATE` on the row inside `DB::transaction()`. A real
  existing-row lock is exclusive and blocks a second transaction regardless
  of isolation level — it isn't a gap lock, so it needs no isolation-level
  pinning and no named lock.
- **First row for a pair**: `record()` first tries the locked `SELECT`; if
  nothing matches, it tries to `INSERT`. A second, concurrent "first
  movement" for the same pair blocks on the unique key until the winner
  commits, then fails with a duplicate-key error (`SQLSTATE 23000`) — caught
  and turned into a normal locked re-read of the row the winner just
  created. This is correct under every isolation level because it relies on
  InnoDB's ordinary insert-vs-insert unique-key blocking, not gap locking.

No named lock, no `SET TRANSACTION ISOLATION LEVEL` (which Phase 2.2 also
tried and rejected — MySQL errors if issued while a transaction is already
open, which it always is under `RefreshDatabase` in tests). The general
principle, recorded in `.wolf/cerebrum.md`: **when the invariant has a real
unique constraint, plain `lockForUpdate()` + catch-duplicate-and-retry is
sufficient; a named lock is only needed when nothing in the schema backs the
invariant.**

`InventoryLedger` is the sole writer of both tables (enforced by convention,
mirroring `WarehouseService`), and always re-derives the row it's about to
mutate from inside the lock rather than trusting a value read earlier —
required regardless of locking mechanism, since a caller's in-memory
reference can be stale relative to a write that committed between when it
was obtained and when the lock was acquired.

`inventory:reconcile` (drift detection/repair) follows the same discipline:
each repair recomputes `SUM(movements.quantity)` and writes it inside one
locked transaction per (product, warehouse) row, rather than snapshotting a
bulk report and writing it later — otherwise reconciliation itself could
clobber a legitimate concurrent movement with a stale sum. It never writes
`inventory_movements`; repair only ever means correcting `stock.quantity`.

## Consequences

- Tenant/quantity correctness rests on an ordinary MySQL constraint instead
  of an isolation-level assumption or an extra locking primitive — simpler
  to reason about and to test (see
  `Modules\Inventory\Tests\Feature\StockConcurrencyTest`, which proves both
  failure modes above are actually blocked using two real database
  connections, not mocks).
- All quantities move through the service as decimal strings (`bcadd`/
  `bccomp`), never PHP floats, per `docs/PHASE-2-PLAN.md` §8.
- Future writers (Phase 3/4 sales & purchases, 2.4 adjustments, 2.5
  transfers) call `InventoryLedger::record()` and get this guarantee for
  free — they must never insert into `inventory_movements` or `stock`
  directly.
