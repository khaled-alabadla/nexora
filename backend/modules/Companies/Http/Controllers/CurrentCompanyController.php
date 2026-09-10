<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Support\Tenancy\CompanyContext;
use Illuminate\Http\JsonResponse;
use Modules\Companies\Http\Requests\UpdateCompanyRequest;
use Modules\Companies\Http\Resources\CompanyResource;

/**
 * Read / update the active company (bound by SetActiveCompany). The company is
 * taken from CompanyContext, never from the request.
 */
final class CurrentCompanyController
{
    public function show(CompanyContext $context): JsonResponse
    {
        return ApiResponse::make(new CompanyResource($context->company()));
    }

    public function update(UpdateCompanyRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $context->company();
        $company->update(['name' => (string) $request->string('name')]);

        return ApiResponse::make(new CompanyResource($company), 'Company updated.');
    }
}
