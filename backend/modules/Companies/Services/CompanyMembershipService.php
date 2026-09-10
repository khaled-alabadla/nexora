<?php

declare(strict_types=1);

namespace Modules\Companies\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;

/**
 * Membership lifecycle within a single company: switching the active company,
 * changing a member's role, and removing a member. All operations are
 * membership-checked; a company id is never taken on trust.
 */
final class CompanyMembershipService
{
    /**
     * Point the user's active company at $company. The user must already be a
     * member of an active company.
     *
     * @throws ModelNotFoundException when the user is not a member (404 — no existence disclosure)
     */
    public function switchActiveCompany(User $user, int $companyId): Company
    {
        /** @var Company $company */
        $company = $user->companies()
            ->whereKey($companyId)
            ->firstOrFail();

        if (! $company->isActive()) {
            throw ValidationException::withMessages([
                'company' => 'That company is not active.',
            ]);
        }

        $user->forceFill(['current_company_id' => $company->getKey()])->save();

        return $company;
    }

    /**
     * Change $member's role in $company. Cannot demote the last owner.
     *
     * @throws AuthorizationException
     */
    public function changeRole(Company $company, User $member, Role $role): CompanyUser
    {
        $membership = $this->membership($company, $member);

        if ($membership->role_id !== $role->getKey()
            && $this->roleSlug($membership) === Role::OWNER
            && $this->ownerCount($company) === 1) {
            throw new AuthorizationException('A company must keep at least one owner.');
        }

        $membership->forceFill(['role_id' => $role->getKey()])->save();

        return $membership->load('role');
    }

    /**
     * Remove $member from $company. Cannot remove the last owner.
     *
     * @throws AuthorizationException
     */
    public function removeMember(Company $company, User $member): void
    {
        $membership = $this->membership($company, $member);

        if ($this->roleSlug($membership) === Role::OWNER && $this->ownerCount($company) === 1) {
            throw new AuthorizationException('A company must keep at least one owner.');
        }

        $membership->delete();

        if ($member->current_company_id === $company->getKey()) {
            $member->forceFill(['current_company_id' => null])->save();
        }
    }

    private function membership(Company $company, User $member): CompanyUser
    {
        return CompanyUser::query()
            ->with('role')
            ->where('company_id', $company->getKey())
            ->where('user_id', $member->getKey())
            ->firstOrFail();
    }

    private function roleSlug(CompanyUser $membership): string
    {
        /** @var Role $role */
        $role = $membership->role;

        return $role->slug;
    }

    private function ownerCount(Company $company): int
    {
        return CompanyUser::query()
            ->where('company_id', $company->getKey())
            ->where('role_id', Role::query()->where('slug', Role::OWNER)->value('id'))
            ->count();
    }
}
