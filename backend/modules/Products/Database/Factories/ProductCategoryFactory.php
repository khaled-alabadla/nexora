<?php

declare(strict_types=1);

namespace Modules\Products\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Products\Models\ProductCategory;

/**
 * @extends Factory<ProductCategory>
 *
 * Note: ProductCategory is tenant-scoped — persist inside a bound
 * CompanyContext (see the actingInCompany() test helper).
 */
final class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()),
            'status' => ProductCategory::STATUS_ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ProductCategory::STATUS_INACTIVE]);
    }
}
