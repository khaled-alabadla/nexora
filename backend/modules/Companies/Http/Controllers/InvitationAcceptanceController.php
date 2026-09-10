<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Companies\Http\Resources\CompanyResource;
use Modules\Companies\Services\CompanyInvitationService;

/**
 * Accept an invitation. Runs with no active company: trust comes from the
 * emailed token plus the requirement that the signed-in user owns the invited
 * address.
 */
final class InvitationAcceptanceController
{
    public function store(Request $request, string $token, CompanyInvitationService $invitations): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $company = $invitations->accept($token, $user);

        return ApiResponse::make(
            new CompanyResource($company),
            "You've joined {$company->name}.",
        );
    }
}
