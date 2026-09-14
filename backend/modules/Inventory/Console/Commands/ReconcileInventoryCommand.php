<?php

declare(strict_types=1);

namespace Modules\Inventory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Detects and repairs drift between the `stock` projection and the
 * `inventory_movements` ledger, the source of truth (PHASE-2-PLAN §4/§7.1).
 *
 * Never touches `inventory_movements` — repair only ever means recomputing
 * and overwriting a `stock.quantity` value; ledger history is never altered.
 * Runs outside any tenant context (bypasses BelongsToCompany entirely via
 * raw query builder calls) so it can sweep every company in one pass.
 */
final class ReconcileInventoryCommand extends Command
{
    protected $signature = 'inventory:reconcile
        {--company= : Only reconcile this company id}
        {--dry-run : Report drift without writing changes}';

    protected $description = 'Recompute the stock projection from the inventory ledger and repair any drift';

    public function handle(): int
    {
        $companyId = $this->option('company') !== null ? (int) $this->option('company') : null;
        $dryRun = (bool) $this->option('dry-run');

        $ledgerSums = $this->ledgerSums($companyId);
        $existingStock = $this->existingStock($companyId);
        $keys = array_unique(array_merge(array_keys($ledgerSums), array_keys($existingStock)));

        $checked = 0;
        $driftCount = 0;

        foreach ($keys as $key) {
            $checked++;
            [$cId, $pId, $wId] = array_map('intval', explode(':', $key));

            // --dry-run reads the one bulk snapshot taken above (O(1) map
            // lookups) — fine for a report, where a little staleness against
            // concurrent live traffic is acceptable because nothing gets
            // written. A real repair still recomputes fresh under a lock,
            // per row, to stay correct against concurrent writers.
            $result = $dryRun
                ? $this->detect($key, $ledgerSums, $existingStock)
                : $this->repair($cId, $pId, $wId);

            if ($result === null) {
                continue;
            }

            $driftCount++;
            $this->line(sprintf(
                '  drift company=%d product=%d warehouse=%d: stock=%s ledger=%s',
                $cId, $pId, $wId, $result['before'], $result['after'],
            ));
        }

        $this->components->info(sprintf(
            '%s %d/%d stock row(s) with drift.',
            $dryRun ? 'Found' : 'Repaired',
            $driftCount,
            $checked,
        ));

        return self::SUCCESS;
    }

    /**
     * O(1) lookup against the bulk snapshot handle() already fetched once —
     * never re-queries per key (a --dry-run sweep across a large dataset
     * must stay one pass, not one pass plus two extra round-trips per row).
     *
     * @param  array<string, string>  $ledgerSums
     * @param  array<string, array{id: int, quantity: string}>  $existingStock
     * @return array{before: string, after: string}|null
     */
    private function detect(string $key, array $ledgerSums, array $existingStock): ?array
    {
        $sum = $ledgerSums[$key] ?? '0.0000';
        $row = $existingStock[$key] ?? null;

        if ($row !== null && bccomp($row['quantity'], $sum, 4) === 0) {
            return null;
        }

        return ['before' => $row['quantity'] ?? '(none)', 'after' => $sum];
    }

    /**
     * Recomputes the ledger sum and locks the stock row (or race-safely
     * creates it) in the *same* transaction, so the value written can never
     * be stale relative to a movement InventoryLedger::record() commits
     * concurrently — recompute-then-write under the lock, not
     * snapshot-then-write, exactly mirroring record()'s own locking.
     *
     * @return array{before: string, after: string}|null null when nothing needed writing
     */
    private function repair(int $companyId, int $productId, int $warehouseId): ?array
    {
        return DB::transaction(function () use ($companyId, $productId, $warehouseId): ?array {
            $sum = $this->normalizeDecimal((string) (
                DB::table('inventory_movements')
                    ->where('company_id', $companyId)
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->sum('quantity')
            ));

            $row = DB::table('stock')
                ->where('company_id', $companyId)
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if ($row !== null) {
                $before = $this->normalizeDecimal((string) $row->quantity);

                if (bccomp($before, $sum, 4) === 0) {
                    return null;
                }

                DB::table('stock')->where('id', $row->id)->update([
                    'quantity' => $sum,
                    'updated_at' => now(),
                ]);

                return ['before' => $before, 'after' => $sum];
            }

            if (bccomp($sum, '0', 4) === 0) {
                // No stock row and nothing in the ledger either — nothing to do.
                return null;
            }

            try {
                DB::table('stock')->insert([
                    'company_id' => $companyId,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $sum,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['before' => '(none)', 'after' => $sum];
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }

                // InventoryLedger created the row concurrently — it already
                // holds the correct value for whatever it just recorded; a
                // future reconcile run will catch any real drift.
                return null;
            }
        });
    }

    /**
     * @return array<string, string> "company:product:warehouse" => SUM(quantity) as a normalized decimal string
     */
    private function ledgerSums(?int $companyId): array
    {
        $query = DB::table('inventory_movements')
            ->selectRaw('company_id, product_id, warehouse_id, SUM(quantity) as total')
            ->groupBy('company_id', 'product_id', 'warehouse_id');

        $this->scope($query, $companyId);

        $sums = [];

        foreach ($query->get() as $row) {
            $sums[$this->key((int) $row->company_id, (int) $row->product_id, (int) $row->warehouse_id)]
                = $this->normalizeDecimal((string) $row->total);
        }

        return $sums;
    }

    /**
     * @return array<string, array{id: int, quantity: string}>
     */
    private function existingStock(?int $companyId): array
    {
        $query = DB::table('stock')->select('id', 'company_id', 'product_id', 'warehouse_id', 'quantity');

        $this->scope($query, $companyId);

        $rows = [];

        foreach ($query->get() as $row) {
            $rows[$this->key((int) $row->company_id, (int) $row->product_id, (int) $row->warehouse_id)] = [
                'id' => (int) $row->id,
                'quantity' => $this->normalizeDecimal((string) $row->quantity),
            ];
        }

        return $rows;
    }

    private function scope(Builder $query, ?int $companyId): void
    {
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }
    }

    private function key(int $companyId, int $productId, int $warehouseId): string
    {
        return "{$companyId}:{$productId}:{$warehouseId}";
    }

    private function normalizeDecimal(string $value): string
    {
        return bcadd($value, '0', 4);
    }
}
