<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Companies\Models\CompanyInvitation;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;
use Modules\Companies\Notifications\CompanyInvitationNotification;

it('sends an invitation and stores only the token hash', function () {
    Notification::fake();
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('company/invitations'), [
        'email' => 'NewHire@Example.com',
        'role' => Role::ACCOUNTANT,
    ])->assertCreated()->assertJsonPath('data.email', 'newhire@example.com');

    $invitation = CompanyInvitation::withoutCompanyScope(fn () => CompanyInvitation::first());
    expect($invitation->company_id)->toBe($company->id)
        ->and($invitation->token)->toHaveLength(64)
        ->and($invitation->email)->toBe('newhire@example.com');

    Notification::assertSentOnDemand(CompanyInvitationNotification::class);
});

it('rejects inviting an existing member', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('company/invitations'), [
        'email' => $owner->email,
        'role' => Role::EMPLOYEE,
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');
});

it('rejects a duplicate pending invitation', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $payload = ['email' => 'dupe@example.com', 'role' => Role::EMPLOYEE];
    $this->postJson(apiUrl('company/invitations'), $payload)->assertCreated();
    $this->postJson(apiUrl('company/invitations'), $payload)->assertStatus(422);
});

it('forbids inviting without member.invite permission', function () {
    [$company, $owner] = companyWithOwner();
    $accountant = User::factory()->create();
    addMember($company, $accountant, Role::ACCOUNTANT);
    $accountant->forceFill(['current_company_id' => $company->id])->save();

    $this->actingAs($accountant->refresh());
    $this->postJson(apiUrl('company/invitations'), ['email' => 'x@example.com', 'role' => Role::EMPLOYEE])
        ->assertStatus(403);
});

it('accepts an invitation and creates the membership', function () {
    [$company, $owner] = companyWithOwner();
    $invitee = User::factory()->create(['email' => 'invitee@example.com', 'current_company_id' => null]);

    $token = inviteToken($company, $owner, 'invitee@example.com', Role::SALES_REP);

    $this->actingAs($invitee->refresh());
    $this->postJson(apiUrl("invitations/{$token}/accept"))
        ->assertOk()
        ->assertJsonPath('data.id', $company->id);

    expect(CompanyUser::where('company_id', $company->id)->where('user_id', $invitee->id)->value('role_id'))
        ->toBe(Role::where('slug', Role::SALES_REP)->value('id'))
        ->and($invitee->refresh()->current_company_id)->toBe($company->id);
});

it('refuses acceptance by a different account than the invited address', function () {
    [$company, $owner] = companyWithOwner();
    $someoneElse = User::factory()->create(['email' => 'someone.else@example.com']);

    $token = inviteToken($company, $owner, 'invited@example.com', Role::EMPLOYEE);

    $this->actingAs($someoneElse);
    $this->postJson(apiUrl("invitations/{$token}/accept"))
        ->assertStatus(422)->assertJsonValidationErrorFor('token');

    expect(CompanyUser::where('company_id', $company->id)->count())->toBe(1);
});

it('rejects an expired invitation', function () {
    [$company, $owner] = companyWithOwner();
    $invitee = User::factory()->create(['email' => 'late@example.com']);

    $token = inviteToken($company, $owner, 'late@example.com', Role::EMPLOYEE, expired: true);

    $this->actingAs($invitee);
    $this->postJson(apiUrl("invitations/{$token}/accept"))
        ->assertStatus(422)->assertJsonValidationErrorFor('token');
});

it('rejects an unknown token', function () {
    $invitee = User::factory()->create();
    $this->actingAs($invitee);

    $this->postJson(apiUrl('invitations/'.str_repeat('a', 48).'/accept'))->assertNotFound();
});

it('cannot accept the same invitation twice', function () {
    [$company, $owner] = companyWithOwner();
    $invitee = User::factory()->create(['email' => 'twice@example.com']);
    $token = inviteToken($company, $owner, 'twice@example.com', Role::EMPLOYEE);

    $this->actingAs($invitee);
    $this->postJson(apiUrl("invitations/{$token}/accept"))->assertOk();
    $this->postJson(apiUrl("invitations/{$token}/accept"))->assertStatus(422);
});

/**
 * Persist an invitation directly (bypassing the tenant scope, as the service
 * does for acceptance) with a known plaintext token, and return that token.
 */
function inviteToken(
    Modules\Companies\Models\Company $company,
    User $inviter,
    string $email,
    string $roleSlug,
    bool $expired = false,
): string {
    $plain = Illuminate\Support\Str::random(48);

    CompanyInvitation::withoutCompanyScope(function () use ($company, $inviter, $email, $roleSlug, $expired, $plain): void {
        CompanyInvitation::forceCreate([
            'company_id' => $company->getKey(),
            'role_id' => Role::query()->where('slug', $roleSlug)->value('id'),
            'invited_by' => $inviter->getKey(),
            'email' => Illuminate\Support\Str::lower($email),
            'token' => hash('sha256', $plain),
            'expires_at' => $expired
                ? Illuminate\Support\Carbon::now()->subDay()
                : Illuminate\Support\Carbon::now()->addDays(7),
        ]);
    });

    return $plain;
}
