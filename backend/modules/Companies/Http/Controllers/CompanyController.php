<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Companies\Http\Requests\StoreCompanyRequest;
use Modules\Companies\Http\Resources\MembershipResource;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Services\CompanyProvisioner;

/**
 * The caller's companies. Neither endpoint requires an active company — this is
 * the surface a user without a selected company still needs.
 */
final class CompanyController
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $memberships = $user->memberships()
            ->with(['company', 'role'])
            ->get()
            ->sortBy(fn (CompanyUser $m): string => (string) $m->company?->name)
            ->values();

        return ApiResponse::make(MembershipResource::collection($memberships));
    }

    public function store(StoreCompanyRequest $request, CompanyProvisioner $provisioner): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $company = $provisioner->create($user, (string) $request->string('name'));

        if ($user->current_company_id === null) {
            $user->forceFill(['current_company_id' => $company->getKey()])->save();
        }

        $membership = $user->memberships()
            ->with(['company', 'role'])
            ->where('company_id', $company->getKey())
            ->firstOrFail();

        return ApiResponse::created(new MembershipResource($membership), 'Company created.');
    }
}
