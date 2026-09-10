<?php

declare(strict_types=1);

namespace Modules\Companies\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Companies\Models\CompanyInvitation;
use Modules\Companies\Models\Role;

/**
 * @extends Factory<CompanyInvitation>
 *
 * Note: CompanyInvitation is tenant-scoped — persist inside a bound
 * CompanyContext (see the actingInCompany() test helper) or the model's
 * `withoutCompanyScope()` bypass.
 */
final class CompanyInvitationFactory extends Factory
{
    protected $model = CompanyInvitation::class;

    public function definition(): array
    {
        return [
            'role_id' => fn () => Role::query()->where('slug', Role::EMPLOYEE)->value('id'),
            'invited_by' => fn () => User::factory(),
            'email' => Str::lower($this->faker->unique()->safeEmail()),
            'token' => hash('sha256', Str::random(48)),
            'expires_at' => Carbon::now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => Carbon::now()->subDay()]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['accepted_at' => Carbon::now()->subHour()]);
    }
}
