<?php

declare(strict_types=1);

use App\Support\Authorization\Permissions;
use Modules\Companies\Models\Role;

it('returns the authenticated user, their companies, active company and permissions', function () {
    [$company, $owner] = companyWithOwner();

    $this->actingAs($owner);

    $this->getJson(apiUrl('auth/me'))
        ->assertOk()
        ->assertJsonPath('data.user.id', $owner->id)
        ->assertJsonPath('data.current_company.id', $company->id)
        ->assertJsonPath('data.current_company.role.slug', Role::OWNER)
        ->assertJsonPath('data.companies.0.id', $company->id)
        ->assertJsonCount(count(Permissions::slugs()), 'data.permissions');
});

it('reports no active company and empty permissions when none is selected', function () {
    [$company, $owner] = companyWithOwner();
    $owner->forceFill(['current_company_id' => null])->save();

    $this->actingAs($owner->refresh());

    $this->getJson(apiUrl('auth/me'))
        ->assertOk()
        ->assertJsonPath('data.current_company', null)
        ->assertJsonPath('data.permissions', [])
        ->assertJsonPath('data.companies.0.id', $company->id);
});

it('scopes permissions to the active company role', function () {
    [$companyA, $owner] = companyWithOwner();
    [$companyB, $member] = companyWithOwner();
    addMember($companyB, $owner, Role::EMPLOYEE);

    // owner is Owner in A, Employee in B — switch active company to B
    $owner->forceFill(['current_company_id' => $companyB->id])->save();
    $this->actingAs($owner->refresh());

    $this->getJson(apiUrl('auth/me'))
        ->assertOk()
        ->assertJsonPath('data.current_company.role.slug', Role::EMPLOYEE)
        ->assertJsonPath('data.permissions', []);
});
