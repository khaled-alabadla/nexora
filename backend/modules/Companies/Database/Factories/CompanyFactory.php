<?php

declare(strict_types=1);

namespace Modules\Companies\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Companies\Models\Company;

/**
 * @extends Factory<Company>
 */
final class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'status' => Company::STATUS_ACTIVE,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => Company::STATUS_SUSPENDED]);
    }
}
