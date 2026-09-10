<?php

declare(strict_types=1);

namespace Modules\Identity\Support;

use App\Models\User;
use App\Support\Authorization\GrantedPermissions;
use Modules\Companies\Http\Resources\MembershipResource;
use Modules\Companies\Models\CompanyUser;
use Modules\Identity\Http\Resources\UserResource;

/**
 * Assembles the "who am I" payload returned by register / login / GET me:
 * the user, the companies they belong to, the active company, and the
 * permissions effective for them in that active company.
 */
final class SessionPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $user): array
    {
        $memberships = $user->memberships()->with(['company', 'role'])->get();

        $current = $user->current_company_id !== null
            ? $memberships->firstWhere('company_id', $user->current_company_id)
            : null;

        return [
            'user' => (new UserResource($user))->resolve(),
            'current_company' => $current !== null
                ? (new MembershipResource($current))->resolve()
                : null,
            'companies' => MembershipResource::collection(
                $memberships->sortBy(fn (CompanyUser $m): string => (string) $m->company?->name)->values()
            )->resolve(),
            'permissions' => GrantedPermissions::for($current?->role),
        ];
    }
}
