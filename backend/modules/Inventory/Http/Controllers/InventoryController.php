<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Support\Http\QueryFilter;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Http\Resources\InventoryMovementResource;
use Modules\Inventory\Http\Resources\StockResource;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;

/**
 * Read-only for now: the stock projection and the ledger behind it
 * (PHASE-2-PLAN §5). Writes go through InventoryLedger, called by the
 * adjustments (2.4) and transfers (2.5) endpoints — not this controller.
 */
final class InventoryController
{
    public function stock(QueryFilter $filter): JsonResponse
    {
        $query = $filter->apply(
            Stock::query()->with(['product', 'warehouse']),
            filterable: ['product_id', 'warehouse_id'],
            sortable: ['quantity', 'updated_at'],
            defaultSort: '-updated_at',
        );

        return ApiResponse::paginated($query->paginate($filter->perPage())->through(
            fn (Stock $stock): StockResource => new StockResource($stock)
        ));
    }

    public function movements(QueryFilter $filter): JsonResponse
    {
        $query = $filter->apply(
            InventoryMovement::query()->with(['product', 'warehouse']),
            filterable: ['product_id', 'warehouse_id', 'type'],
            sortable: ['created_at'],
            defaultSort: '-created_at',
        );

        // created_at is second-precision, so two movements recorded in the
        // same second would otherwise sort in an undefined order — id is
        // monotonically increasing with insertion order, so it's a stable
        // tiebreaker for the documented "newest first" contract.
        $query->orderByDesc('id');

        return ApiResponse::paginated($query->paginate($filter->perPage())->through(
            fn (InventoryMovement $movement): InventoryMovementResource => new InventoryMovementResource($movement)
        ));
    }
}
