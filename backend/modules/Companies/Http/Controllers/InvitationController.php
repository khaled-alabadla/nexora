<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Companies\Http\Requests\InviteMemberRequest;
use Modules\Companies\Http\Resources\InvitationResource;
use Modules\Companies\Models\CompanyInvitation;
use Modules\Companies\Models\Role;
use Modules\Companies\Services\CompanyInvitationService;

/**
 * Invitations for the active company. CompanyInvitation is tenant-scoped
 * (BelongsToCompany), so these queries are automatically bound to the caller's
 * company.
 */
final class InvitationController
{
    public function index(): JsonResponse
    {
        $invitations = CompanyInvitation::query()
            ->with('role')
            ->latest()
            ->get();

        return ApiResponse::make(InvitationResource::collection($invitations));
    }

    public function store(InviteMemberRequest $request, CompanyInvitationService $invitations): JsonResponse
    {
        /** @var User $inviter */
        $inviter = $request->user();

        $role = Role::query()->where('slug', (string) $request->string('role'))->firstOrFail();

        $invitation = $invitations->invite(
            $inviter,
            (string) $request->string('email'),
            $role,
        );

        return ApiResponse::created(new InvitationResource($invitation), 'Invitation sent.');
    }

    public function destroy(int $invitation, CompanyInvitationService $invitations): Response
    {
        // The global tenant scope constrains this lookup to the active company;
        // a foreign id resolves to 404.
        $model = CompanyInvitation::query()->findOrFail($invitation);

        $invitations->revoke($model);

        return ApiResponse::noContent();
    }
}
