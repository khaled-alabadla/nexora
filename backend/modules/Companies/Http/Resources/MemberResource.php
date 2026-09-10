<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;

/**
 * One member of the active company (a company_user row with user + role loaded).
 *
 * @mixin CompanyUser
 */
final class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->user;
        /** @var Role $role */
        $role = $this->role;

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => new RoleResource($role),
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
