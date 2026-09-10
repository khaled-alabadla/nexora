<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case bindings
|--------------------------------------------------------------------------
|
| Bind TestCase to the core suites and to every module's test directory so
| modules need no per-module Pest bootstrapping (see ADR-0002 / ADR-0005).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(__DIR__.'/../modules');

/*
|--------------------------------------------------------------------------
| Stateful requests
|--------------------------------------------------------------------------
|
| The first-party SPA authenticates with Sanctum cookie sessions (ADR-0004),
| which only engage for requests coming "from the frontend". Feature tests
| stamp the frontend origin so register/login/logout exercise the real
| session-backed path.
|
*/

pest()->beforeEach(function () {
    $this->withHeader('Origin', config('app.url'));
})->in('Feature', __DIR__.'/../modules');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeBalanced', function () {
    // Placeholder for accounting assertions (Phase 5): debits === credits.
    return $this->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function apiUrl(string $path = ''): string
{
    return rtrim(config('nexora.api_prefix').'/'.ltrim($path, '/'), '/');
}

/**
 * Create a company owned by $owner (a fresh verified user if omitted) together
 * with the Owner membership, using the real provisioner.
 *
 * @return array{0: Modules\Companies\Models\Company, 1: App\Models\User}
 */
function companyWithOwner(?App\Models\User $owner = null): array
{
    $owner ??= App\Models\User::factory()->create();
    $company = app(Modules\Companies\Services\CompanyProvisioner::class)->create($owner, fake()->company());
    $owner->forceFill(['current_company_id' => $company->getKey()])->save();

    return [$company->refresh(), $owner->refresh()];
}

/**
 * Attach $user to $company with the given role slug. Returns the membership.
 */
function addMember(
    Modules\Companies\Models\Company $company,
    App\Models\User $user,
    string $roleSlug = Modules\Companies\Models\Role::EMPLOYEE,
): Modules\Companies\Models\CompanyUser {
    return Modules\Companies\Models\CompanyUser::query()->create([
        'company_id' => $company->getKey(),
        'user_id' => $user->getKey(),
        'role_id' => Modules\Companies\Models\Role::query()->where('slug', $roleSlug)->value('id'),
    ]);
}

/**
 * Authenticate $user for the "web"/sanctum guard AND bind $company as the
 * active tenant context — mirroring what SetActiveCompany does for a real
 * request. Pass $company = null to authenticate without an active company.
 */
function actingInCompany(App\Models\User $user, ?Modules\Companies\Models\Company $company = null): void
{
    test()->actingAs($user);

    $context = app(App\Support\Tenancy\CompanyContext::class);

    if ($company === null) {
        $context->clear();

        return;
    }

    $context->set($company);
}
