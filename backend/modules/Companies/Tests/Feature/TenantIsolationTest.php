<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;

/**
 * The mandatory cross-tenant isolation suite (CLAUDE.md / ADR-0006). A user who
 * is active in company A must never read or mutate company B's data, even when
 * they are also a member of B.
 */
beforeEach(function () {
    [$this->companyA, $this->alice] = companyWithOwner(); // Alice owns A
    [$this->companyB, $this->bob] = companyWithOwner();   // Bob owns B

    // Alice is also an Employee of B, but her active company is A.
    addMember($this->companyB, $this->alice, Role::EMPLOYEE);
    $this->alice->forceFill(['current_company_id' => $this->companyA->id])->save();
});

it('member listing only ever returns the active company', function () {
    $this->actingAs($this->alice->refresh());

    $emails = collect($this->getJson(apiUrl('company/members'))->assertOk()->json('data'))
        ->pluck('email');

    expect($emails)->toContain($this->alice->email)
        ->not->toContain($this->bob->email);
});

it('cannot change a role of a member of another company', function () {
    $this->actingAs($this->alice->refresh());

    // Bob is a member of B, not A → 404 under A's context
    $this->patchJson(apiUrl("company/members/{$this->bob->id}"), ['role' => Role::EMPLOYEE])
        ->assertNotFound();

    expect(CompanyUser::where('company_id', $this->companyB->id)->where('user_id', $this->bob->id)->value('role_id'))
        ->toBe(Role::where('slug', Role::OWNER)->value('id'));
});

it('cannot revoke an invitation belonging to another company', function () {
    $tokenPlain = Illuminate\Support\Str::random(48);
    $foreignInvitation = Modules\Companies\Models\CompanyInvitation::withoutCompanyScope(
        fn () => Modules\Companies\Models\CompanyInvitation::forceCreate([
            'company_id' => $this->companyB->id,
            'role_id' => Role::where('slug', Role::EMPLOYEE)->value('id'),
            'invited_by' => $this->bob->id,
            'email' => 'target@example.com',
            'token' => hash('sha256', $tokenPlain),
            'expires_at' => now()->addDays(7),
        ])
    );

    $this->actingAs($this->alice->refresh());

    $this->deleteJson(apiUrl("company/invitations/{$foreignInvitation->id}"))->assertNotFound();

    expect($foreignInvitation->fresh())->not->toBeNull();
});

it('invitation listing is scoped to the active company', function () {
    Modules\Companies\Models\CompanyInvitation::withoutCompanyScope(
        fn () => Modules\Companies\Models\CompanyInvitation::forceCreate([
            'company_id' => $this->companyB->id,
            'role_id' => Role::where('slug', Role::EMPLOYEE)->value('id'),
            'invited_by' => $this->bob->id,
            'email' => 'b-only@example.com',
            'token' => hash('sha256', 'zz'),
            'expires_at' => now()->addDays(7),
        ])
    );

    $this->actingAs($this->alice->refresh());

    // Alice is Owner of A → allowed to view; list must be empty (invite is B's)
    $this->getJson(apiUrl('company/invitations'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('permissions are evaluated against the active company, not the highest role held', function () {
    // Alice is Owner of A and Employee of B. Switch her active company to B.
    $this->alice->forceFill(['current_company_id' => $this->companyB->id])->save();
    $this->actingAs($this->alice->refresh());

    // As an Employee of B she cannot rename B or view its members.
    $this->putJson(apiUrl('company'), ['name' => 'Grabbed'])->assertStatus(403);
    $this->getJson(apiUrl('company/members'))->assertStatus(403);
});

it('rejects a request whose active company membership was revoked mid-session', function () {
    $this->actingAs($this->alice->refresh());
    $this->getJson(apiUrl('company'))->assertOk();

    // Remove Alice from A entirely.
    CompanyUser::where('company_id', $this->companyA->id)->where('user_id', $this->alice->id)->delete();

    $this->app['auth']->forgetGuards();
    $this->actingAs($this->alice->refresh());

    $this->getJson(apiUrl('company'))
        ->assertStatus(409)
        ->assertJsonPath('code', 'no_active_company');
});
