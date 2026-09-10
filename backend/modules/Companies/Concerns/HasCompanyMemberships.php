<?php

declare(strict_types=1);

namespace Modules\Companies\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;

/**
 * Company membership + authorization surface for the User model.
 *
 * All permission checks resolve against a specific company — by default the
 * user's *current* company (the one SetActiveCompany bound for this request).
 */
trait HasCompanyMemberships
{
    /** @return HasMany<CompanyUser, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    /** @return BelongsToMany<Company, $this, CompanyUser> */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->using(CompanyUser::class)
            ->withPivot(['role_id', 'id'])
            ->withTimestamps();
    }

    /** @return BelongsTo<Company, $this> */
    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function membershipFor(Company $company): ?CompanyUser
    {
        return $this->memberships()
            ->where('company_id', $company->getKey())
            ->first();
    }

    public function belongsToCompany(Company $company): bool
    {
        return $this->memberships()
            ->where('company_id', $company->getKey())
            ->exists();
    }

    public function roleIn(Company $company): ?Role
    {
        return $this->membershipFor($company)?->role;
    }

    public function isOwnerOf(Company $company): bool
    {
        return $this->roleIn($company)?->slug === Role::OWNER;
    }

    /**
     * Does the user hold $permission in $company (default: their current company)?
     * Owners implicitly hold every permission.
     */
    public function hasPermission(string $permission, ?Company $company = null): bool
    {
        $company ??= $this->currentCompany;

        if ($company === null) {
            return false;
        }

        $role = $this->roleIn($company);

        if ($role === null) {
            return false;
        }

        if ($role->isOwner()) {
            return true;
        }

        return $role->permissions()
            ->where('slug', $permission)
            ->exists();
    }
}
