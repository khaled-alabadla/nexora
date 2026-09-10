<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Companies\Models\CompanyInvitation;

/**
 * @mixin CompanyInvitation
 */
final class InvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => new RoleResource($this->whenLoaded('role')),
            'status' => $this->accepted_at !== null ? 'accepted' : ($this->isExpired() ? 'expired' : 'pending'),
            'expires_at' => $this->expires_at->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
