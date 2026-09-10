<?php

declare(strict_types=1);

use Modules\Companies\Models\Company;
use Modules\Companies\Models\Role;

it('switches the active company to another the user belongs to', function () {
    [$companyA, $user] = companyWithOwner();
    [$companyB] = companyWithOwner();
    addMember($companyB, $user, Role::SALES_MANAGER);

    $this->actingAs($user);

    $this->putJson(apiUrl("companies/{$companyB->id}/active"))
        ->assertOk()
        ->assertJsonPath('data.id', $companyB->id)
        ->assertJsonPath('data.role.slug', Role::SALES_MANAGER);

    expect($user->refresh()->current_company_id)->toBe($companyB->id);
});

it('refuses to switch to a company the user does not belong to', function () {
    [$companyA, $user] = companyWithOwner();
    [$foreign] = companyWithOwner();

    $this->actingAs($user);

    $this->putJson(apiUrl("companies/{$foreign->id}/active"))->assertNotFound();

    expect($user->refresh()->current_company_id)->toBe($companyA->id);
});

it('refuses to switch to a suspended company', function () {
    [$companyA, $user] = companyWithOwner();
    [$companyB] = companyWithOwner();
    addMember($companyB, $user, Role::EMPLOYEE);
    $companyB->forceFill(['status' => Company::STATUS_SUSPENDED])->save();

    $this->actingAs($user);

    $this->putJson(apiUrl("companies/{$companyB->id}/active"))->assertStatus(422);
});
