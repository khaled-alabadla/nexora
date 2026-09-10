<?php

declare(strict_types=1);

use App\Support\Tenancy\CompanyContext;
use App\Support\Tenancy\TenantContextMissingException;
use Modules\Companies\Models\CompanyInvitation;
use Modules\Companies\Models\Role;

/**
 * Exercises the BelongsToCompany mechanism (ADR-0006) through CompanyInvitation,
 * the one tenant-scoped model in Phase 1. Phase 2+ business models inherit the
 * same guarantees.
 */
beforeEach(function () {
    [$this->companyA, $this->ownerA] = companyWithOwner();
    [$this->companyB, $this->ownerB] = companyWithOwner();
    $this->context = app(CompanyContext::class);
    $this->employeeRoleId = Role::query()->where('slug', Role::EMPLOYEE)->value('id');
});

function makeInvitation(int $roleId, string $email, ?int $companyId = null): CompanyInvitation
{
    return CompanyInvitation::withoutCompanyScope(fn () => CompanyInvitation::forceCreate([
        'company_id' => $companyId ?? App\Models\User::query()->first()->current_company_id,
        'role_id' => $roleId,
        'invited_by' => App\Models\User::query()->first()->getKey(),
        'email' => $email,
        'token' => hash('sha256', Illuminate\Support\Str::random(48)),
        'expires_at' => now()->addDays(7),
    ]));
}

it('filters every query by the active company', function () {
    makeInvitation($this->employeeRoleId, 'a@example.com', $this->companyA->id);
    makeInvitation($this->employeeRoleId, 'b@example.com', $this->companyB->id);

    $this->context->set($this->companyA);
    expect(CompanyInvitation::pluck('email')->all())->toBe(['a@example.com']);

    $this->context->set($this->companyB);
    expect(CompanyInvitation::pluck('email')->all())->toBe(['b@example.com']);
});

it('forces company_id from the context and ignores any supplied value', function () {
    $this->context->set($this->companyA);

    $invitation = new CompanyInvitation;
    $invitation->forceFill([
        'company_id' => $this->companyB->id, // hostile value
        'role_id' => $this->employeeRoleId,
        'invited_by' => $this->ownerA->id,
        'email' => 'forced@example.com',
        'token' => hash('sha256', 'x'),
        'expires_at' => now()->addDay(),
    ])->save();

    expect($invitation->fresh()->company_id)->toBe($this->companyA->id);
});

it('throws when a tenant query runs with no active company', function () {
    $this->context->clear();

    CompanyInvitation::query()->get();
})->throws(TenantContextMissingException::class);

it('bypasses the scope only inside withoutCompanyScope', function () {
    makeInvitation($this->employeeRoleId, 'a@example.com', $this->companyA->id);
    makeInvitation($this->employeeRoleId, 'b@example.com', $this->companyB->id);

    $this->context->set($this->companyA);

    $all = CompanyInvitation::withoutCompanyScope(fn () => CompanyInvitation::count());
    expect($all)->toBe(2);

    // scope restored afterwards
    expect(CompanyInvitation::count())->toBe(1);
});
