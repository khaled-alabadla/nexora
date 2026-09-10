<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Companies\Http\Resources\MembershipResource;
use Modules\Companies\Services\CompanyMembershipService;

final class ActiveCompanyController
{
    public function update(Request $request, int $company, CompanyMembershipService $memberships): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $memberships->switchActiveCompany($user, $company);

        $membership = $user->memberships()
            ->with(['company', 'role'])
            ->where('company_id', $company)
            ->firstOrFail();

        return ApiResponse::make(new MembershipResource($membership), 'Active company switched.');
    }
}
