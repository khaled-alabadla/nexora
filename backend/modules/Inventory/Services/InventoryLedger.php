<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Support\Tenancy\CompanyContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Products\Models\Product;

/**
 * The single writer of `inventory_movements` + `stock` for the active
 * company (PHASE-2-PLAN.md §5) — every stock-affecting operation (2.4
 * adjustments, 2.5 transfers, Phase 3/4 sales/purchases) calls record().
 * Never insert into either table directly.
 */
final class InventoryLedger
{
    public function __construct(private readonly CompanyContext $context) {}

    /**
     * Appends one signed movement and updates the stock projection in the
     * same transaction.
     *
     * @param  numeric-string  $quantity  signed: +in / -out (PHASE-2-PLAN §7.2) — always a decimal string, never a float (§8)
     * @param  numeric-string|null  $unitCost
     */
    public function record(
        Product $product,
        Warehouse $warehouse,
        string $type,
        string $quantity,
        ?string $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
        bool $force = false,
    ): InventoryMovement {
        if (! in_array($type, InventoryMovement::types(), true)) {
            throw new InvalidArgumentException("Unknown inventory movement type [{$type}].");
        }

        $requiredSign = InventoryMovement::requiredSign($type);

        if ($requiredSign !== null && bccomp($quantity, '0', 4) !== $requiredSign) {
            throw new InvalidArgumentException(
                "Movement type [{$type}] requires a ".($requiredSign > 0 ? 'positive' : 'negative')." quantity, got [{$quantity}].",
            );
        }

        $companyId = $this->context->id();

        // Defense in depth beyond tenant-scoped route-model binding: this is
        // the one place every inventory write passes through, so a future
        // caller that resolves $product/$warehouse incorrectly can never mix
        // company_id (from context) with a foreign product/warehouse id.
        if ($product->company_id !== $companyId || $warehouse->company_id !== $companyId) {
            throw new InvalidArgumentException('Product and warehouse must belong to the active company.');
        }

        return DB::transaction(function () use (
            $companyId, $product, $warehouse, $type, $quantity, $unitCost, $referenceType, $referenceId, $note, $force,
        ): InventoryMovement {
            $stock = $this->lockedStockRow($companyId, $product->getKey(), $warehouse->getKey());

            $newQuantity = bcadd($stock->quantity, $quantity, 4);
            $canForce = $force && in_array($type, InventoryMovement::FORCEABLE_TYPES, true);

            if (bccomp($newQuantity, '0', 4) < 0 && ! $canForce) {
                throw ValidationException::withMessages([
                    'quantity' => 'This movement would take stock below zero.',
                ]);
            }

            $movement = InventoryMovement::query()->create([
                'product_id' => $product->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
                'created_by' => Auth::id(),
            ]);

            $stock->quantity = $newQuantity;
            $stock->save();

            return $movement;
        });
    }

    /**
     * Finds (locked) or race-safely creates the stock row for (product,
     * warehouse).
     *
     * Two requests racing to create the *first* row for a pair are
     * serialized by the table's real unique constraint, not by gap locking:
     * the loser's INSERT blocks on the unique key until the winner commits,
     * then fails with a duplicate-key error that's caught here and turned
     * into a normal locked read of the row the winner just created — correct
     * under every transaction isolation level, unlike a lockForUpdate query
     * that matches zero rows (see WarehouseService::serialized() and the
     * cerebrum.md Do-Not-Repeat entry on gap-lock reliance).
     */
    private function lockedStockRow(int $companyId, int $productId, int $warehouseId): Stock
    {
        $stock = Stock::query()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if ($stock !== null) {
            return $stock;
        }

        try {
            $stock = new Stock(['product_id' => $productId, 'warehouse_id' => $warehouseId]);
            $stock->quantity = '0';
            $stock->save();

            return $stock;
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return Stock::query()
                ->where('company_id', $companyId)
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();
        }
    }
}
