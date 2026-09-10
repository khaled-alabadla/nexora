<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;

/**
 * A company as seen from one user's membership — the company fields plus the
 * caller's role in it. Used for the "my companies" list and the active company.
 *
 * @mixin CompanyUser
 */
final class MembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Company $company */
        $company = $this->company;
        /** @var Role $role */
        $role = $this->role;

        return [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'status' => $company->status,
            'role' => new RoleResource($role),
        ];
    }
}
