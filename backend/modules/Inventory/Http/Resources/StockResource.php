<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Products\Models\Product;

/**
 * @mixin Stock
 */
final class StockResource extends JsonResource
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
            'quantity' => $this->quantity,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
