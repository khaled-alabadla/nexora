<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Products\Models\Product;

/**
 * @extends Factory<InventoryMovement>
 *
 * Note: for fixture setup only (e.g. seeding a read-path test). Tests
 * exercising ledger *behavior* (sign semantics, negative-stock guard,
 * projection updates) must go through InventoryLedger — this factory does
 * not touch the `stock` projection at all.
 */
final class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'type' => InventoryMovement::TYPE_PURCHASE,
            'quantity' => $this->faker->randomFloat(4, 1, 100),
            'unit_cost' => $this->faker->randomFloat(4, 1, 50),
            'note' => null,
        ];
    }

    public function type(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
