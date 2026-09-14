<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Warehouse;

/**
 * @extends Factory<Warehouse>
 *
 * Note: Warehouse is tenant-scoped — persist inside a bound CompanyContext
 * (see the actingInCompany()/withoutTenantScope() test helpers). This factory
 * does not manage the one-default-per-company invariant — go through
 * WarehouseService for that, or set is_default explicitly per test.
 */
final class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->city()).' Warehouse',
            'location' => $this->faker->optional()->address(),
            'is_default' => false,
            'status' => Warehouse::STATUS_ACTIVE,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Warehouse::STATUS_INACTIVE]);
    }
}
