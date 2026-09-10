<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;

it('lists members of the active company for a permitted role', function () {
    [$company, $owner] = companyWithOwner();
    $alice = User::factory()->create(['name' => 'Alice']);
    addMember($company, $alice, Role::ACCOUNTANT);

    $this->actingAs($owner);

    $this->getJson(apiUrl('company/members'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['email' => $alice->email]);
});

it('forbids member listing for a role without member.view', function () {
    [$company, $owner] = companyWithOwner();
    $bob = User::factory()->create();
    addMember($company, $bob, Role::EMPLOYEE);
    $bob->forceFill(['current_company_id' => $company->id])->save();

    $this->actingAs($bob->refresh());
    $this->getJson(apiUrl('company/members'))->assertStatus(403);
});

it('changes a member role', function () {
    [$company, $owner] = companyWithOwner();
    $carol = User::factory()->create();
    addMember($company, $carol, Role::EMPLOYEE);

    $this->actingAs($owner);

    $this->patchJson(apiUrl("company/members/{$carol->id}"), ['role' => Role::SALES_MANAGER])
        ->assertOk()
        ->assertJsonPath('data.role.slug', Role::SALES_MANAGER);

    expect(CompanyUser::where('company_id', $company->id)->where('user_id', $carol->id)->value('role_id'))
        ->toBe(Role::where('slug', Role::SALES_MANAGER)->value('id'));
});

it('cannot assign the owner role by API', function () {
    [$company, $owner] = companyWithOwner();
    $dave = User::factory()->create();
    addMember($company, $dave, Role::EMPLOYEE);

    $this->actingAs($owner);

    $this->patchJson(apiUrl("company/members/{$dave->id}"), ['role' => Role::OWNER])
        ->assertStatus(422)->assertJsonValidationErrorFor('role');
});

it('removes a member', function () {
    [$company, $owner] = companyWithOwner();
    $erin = User::factory()->create();
    addMember($company, $erin, Role::EMPLOYEE);
    $erin->forceFill(['current_company_id' => $company->id])->save();

    $this->actingAs($owner);

    $this->deleteJson(apiUrl("company/members/{$erin->id}"))->assertNoContent();

    expect(CompanyUser::where('company_id', $company->id)->where('user_id', $erin->id)->exists())->toBeFalse()
        ->and($erin->refresh()->current_company_id)->toBeNull();
});

it('cannot remove the last owner', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->deleteJson(apiUrl("company/members/{$owner->id}"))->assertStatus(403);

    expect(CompanyUser::where('company_id', $company->id)->where('user_id', $owner->id)->exists())->toBeTrue();
});

it('cannot demote the last owner', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->patchJson(apiUrl("company/members/{$owner->id}"), ['role' => Role::ADMINISTRATOR])
        ->assertStatus(403);
});

it('404s for a target user who is not a member of the active company', function () {
    [$company, $owner] = companyWithOwner();
    $stranger = User::factory()->create();

    $this->actingAs($owner);

    $this->patchJson(apiUrl("company/members/{$stranger->id}"), ['role' => Role::EMPLOYEE])
        ->assertNotFound();
});
