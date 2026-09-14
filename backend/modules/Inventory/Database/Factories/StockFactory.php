<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Products\Models\Product;

/**
 * @extends Factory<Stock>
 *
 * Note: for fixture setup only (e.g. seeding a read-path test) — go through
 * InventoryLedger to exercise real projection-update behavior.
 */
final class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity' => $this->faker->randomFloat(4, 0, 500),
        ];
    }
}
