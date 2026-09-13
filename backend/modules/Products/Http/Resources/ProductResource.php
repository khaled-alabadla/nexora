<?php

declare(strict_types=1);

namespace Modules\Products\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Products\Models\Product;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category !== null ? new ProductCategoryResource($this->category) : null,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'barcode' => $this->barcode,
            'unit' => $this->unit,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'tax_rate' => $this->tax_rate,
            'minimum_stock' => $this->minimum_stock,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
