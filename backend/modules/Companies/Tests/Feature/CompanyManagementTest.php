<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\Role;

it('lists only the companies the caller belongs to', function () {
    [$companyA, $owner] = companyWithOwner();
    [$companyB] = companyWithOwner();
    addMember($companyB, $owner, Role::ACCOUNTANT);
    [$companyC] = companyWithOwner(); // owner is NOT a member

    $this->actingAs($owner);

    $response = $this->getJson(apiUrl('companies'))->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($companyA->id, $companyB->id)
        ->not->toContain($companyC->id);
    expect(collect($response->json('data'))->firstWhere('id', $companyB->id)['role']['slug'])
        ->toBe(Role::ACCOUNTANT);
});

it('lets a verified user create an additional company as its owner', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('companies'), ['name' => 'Second Venture'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Second Venture')
        ->assertJsonPath('data.role.slug', Role::OWNER);

    expect(Company::where('name', 'Second Venture')->exists())->toBeTrue()
        ->and($owner->refresh()->companies()->count())->toBe(2);
});

it('forbids company creation for unverified users', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    $this->postJson(apiUrl('companies'), ['name' => 'Nope'])->assertStatus(403);
});

it('exposes the system role catalogue to any authenticated user', function () {
    [, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $response = $this->getJson(apiUrl('roles'))->assertOk();

    $slugs = collect($response->json('data'))->pluck('slug');
    expect($slugs)->toContain('owner', 'administrator', 'employee')
        ->and($response->json('data.0.slug'))->toBe('owner'); // highest level first
});

it('shows the active company', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->getJson(apiUrl('company'))
        ->assertOk()
        ->assertJsonPath('data.id', $company->id)
        ->assertJsonPath('data.slug', $company->slug);
});

it('returns 409 when the caller has no active company', function () {
    [$company, $owner] = companyWithOwner();
    $owner->forceFill(['current_company_id' => null])->save();
    $this->actingAs($owner->refresh());

    $this->getJson(apiUrl('company'))
        ->assertStatus(409)
        ->assertJsonPath('code', 'no_active_company');
});

it('lets a permitted role rename the company and blocks others', function () {
    [$company, $owner] = companyWithOwner();
    $employee = User::factory()->create();
    addMember($company, $employee, Role::EMPLOYEE);
    $employee->forceFill(['current_company_id' => $company->id])->save();

    $this->actingAs($employee->refresh());
    $this->putJson(apiUrl('company'), ['name' => 'Hijacked'])->assertStatus(403);

    $this->actingAs($owner);
    $this->putJson(apiUrl('company'), ['name' => 'Renamed Co'])->assertOk();

    expect($company->refresh()->name)->toBe('Renamed Co');
});

it('clears a stale active company pointer and returns 409', function () {
    [$company, $owner] = companyWithOwner();
    $company->forceFill(['status' => Company::STATUS_SUSPENDED])->save();
    $this->actingAs($owner);

    $this->getJson(apiUrl('company'))->assertStatus(409);

    expect($owner->refresh()->current_company_id)->toBeNull();
});
