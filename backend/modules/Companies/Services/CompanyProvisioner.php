<?php

declare(strict_types=1);

namespace Modules\Companies\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;

/**
 * Creates a company and its founding Owner membership as one atomic unit.
 *
 * Used by registration (first company) and by an authenticated user creating
 * an additional company. Never trusts a client company id — the caller passes
 * the owning User explicitly.
 */
final class CompanyProvisioner
{
    /**
     * @return Company The freshly created company, with the owner membership loaded.
     */
    public function create(User $owner, string $name): Company
    {
        return DB::transaction(function () use ($owner, $name): Company {
            $company = new Company;
            $company->name = trim($name);
            $company->slug = $this->uniqueSlug($name);
            $company->status = Company::STATUS_ACTIVE;
            $company->save();

            CompanyUser::query()->create([
                'company_id' => $company->getKey(),
                'user_id' => $owner->getKey(),
                'role_id' => Role::query()->where('slug', Role::OWNER)->value('id'),
            ]);

            return $company->load('memberships');
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;

        while (Company::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(6));
        }

        return $slug;
    }
}
