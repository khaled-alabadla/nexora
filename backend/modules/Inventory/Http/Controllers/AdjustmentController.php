<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Http\Requests\StoreAdjustmentRequest;
use Modules\Inventory\Http\Resources\InventoryMovementResource;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryLedger;
use Modules\Products\Models\Product;

/**
 * `POST /inventory/adjustments` — the only public writer that can set
 * `type: adjustment|damage` and `force` on InventoryLedger::record()
 * (PHASE-2-PLAN.md §5/§7.4/§7.8). Every line of one request is recorded in
 * a single outer transaction: either the whole adjustment lands or none of
 * it does.
 */
final class AdjustmentController
{
    public function store(StoreAdjustmentRequest $request, InventoryLedger $ledger): JsonResponse
    {
        $warehouse = Warehouse::query()->findOrFail($request->integer('warehouse_id'));
        $type = $request->string('type')->toString();
        $force = $request->boolean('force');

        /** @var list<array{product_id: int, quantity_delta: string, unit_cost?: string|null, reason?: string|null}> $lines */
        $lines = $request->array('lines');

        $movements = DB::transaction(function () use ($ledger, $warehouse, $type, $force, $lines): array {
            return array_map(
                fn (array $line): InventoryMovement => $ledger->record(
                    product: Product::query()->findOrFail($line['product_id']),
                    warehouse: $warehouse,
                    type: $type,
                    quantity: (string) $line['quantity_delta'],
                    unitCost: isset($line['unit_cost']) ? (string) $line['unit_cost'] : null,
                    note: $line['reason'] ?? null,
                    force: $force,
                ),
                $lines,
            );
        });

        return ApiResponse::created(
            InventoryMovementResource::collection($movements),
            count($movements) === 1 ? 'Adjustment recorded.' : 'Adjustments recorded.',
        );
    }
}
