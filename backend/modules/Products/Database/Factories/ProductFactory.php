<?php

declare(strict_types=1);

namespace Modules\Products\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Products\Models\Product;

/**
 * @extends Factory<Product>
 *
 * Note: Product is tenant-scoped — persist inside a bound CompanyContext (see
 * the actingInCompany() test helper).
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => null,
            'sku' => Str::upper(Str::random(8)),
            'name' => ucfirst($this->faker->unique()->word()).' '.$this->faker->word(),
            'description' => $this->faker->optional()->sentence(),
            'barcode' => null,
            'unit' => 'pcs',
            'cost_price' => $this->faker->randomFloat(4, 1, 500),
            'selling_price' => $this->faker->randomFloat(4, 1, 800),
            'tax_rate' => 0,
            'minimum_stock' => 0,
            'status' => Product::STATUS_ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Product::STATUS_INACTIVE]);
    }

    public function withMinimumStock(float $quantity): static
    {
        return $this->state(fn () => ['minimum_stock' => $quantity]);
    }
}
