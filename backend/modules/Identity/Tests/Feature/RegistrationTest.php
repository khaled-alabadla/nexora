<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;

it('registers a user, creates their first company, and signs them in', function () {
    Event::fake([Registered::class]);

    $response = $this->postJson(apiUrl('auth/register'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'Str0ng-passphrase!',
        'password_confirmation' => 'Str0ng-passphrase!',
        'company_name' => 'Analytical Engines',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'ada@example.com')
        ->assertJsonPath('data.user.email_verified', false)
        ->assertJsonPath('data.current_company.name', 'Analytical Engines')
        ->assertJsonPath('data.current_company.role.slug', Role::OWNER);

    $user = User::where('email', 'ada@example.com')->sole();

    expect($user->current_company_id)->not->toBeNull()
        ->and(Company::count())->toBe(1)
        ->and(CompanyUser::where('user_id', $user->id)->where('role_id', Role::where('slug', Role::OWNER)->value('id'))->exists())->toBeTrue();

    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(Registered::class);
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson(apiUrl('auth/register'), [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'Str0ng-passphrase!',
        'password_confirmation' => 'Str0ng-passphrase!',
        'company_name' => 'Whatever',
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');
});

it('requires a company name', function () {
    $this->postJson(apiUrl('auth/register'), [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => 'Str0ng-passphrase!',
        'password_confirmation' => 'Str0ng-passphrase!',
    ])->assertStatus(422)->assertJsonValidationErrorFor('company_name');
});

it('enforces password confirmation and strength', function () {
    $this->postJson(apiUrl('auth/register'), [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => 'weak',
        'password_confirmation' => 'nope',
        'company_name' => 'Co',
    ])->assertStatus(422)->assertJsonValidationErrorFor('password');
});

it('rolls back the user when company provisioning fails', function () {
    // Force the provisioner to blow up mid-transaction by removing the Owner role.
    Role::where('slug', Role::OWNER)->delete();

    $this->postJson(apiUrl('auth/register'), [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'Str0ng-passphrase!',
        'password_confirmation' => 'Str0ng-passphrase!',
        'company_name' => 'Analytical Engines',
    ])->assertStatus(500);

    expect(User::where('email', 'ada@example.com')->exists())->toBeFalse()
        ->and(Company::count())->toBe(0);
});
