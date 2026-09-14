<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Products\Models\Product;

/**
 * @mixin InventoryMovement
 */
final class InventoryMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', function () {
                /** @var Product $product */
                $product = $this->product;

                return ['id' => $product->id, 'sku' => $product->sku, 'name' => $product->name];
            }),
            'warehouse' => $this->whenLoaded('warehouse', function () {
                /** @var Warehouse $warehouse */
                $warehouse = $this->warehouse;

                return ['id' => $warehouse->id, 'name' => $warehouse->name];
            }),
            'type' => $this->type,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
