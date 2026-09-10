<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Middleware;

use App\Support\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the caller's active company into CompanyContext (see ADR-0006).
 *
 * Applied to the tenant-scoped route group only. If the user has no valid
 * active company, responds 409 `no_active_company` (never 500) so the SPA can
 * prompt a company switch; a stale pointer is cleared.
 */
final class SetActiveCompany
{
    public function __construct(private readonly CompanyContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $companyId = $user->current_company_id;

        if ($companyId === null) {
            return $this->noActiveCompany();
        }

        $membership = $user->memberships()
            ->where('company_id', $companyId)
            ->with('company')
            ->first();

        $company = $membership?->company;

        if ($company === null || ! $company->isActive()) {
            $user->forceFill(['current_company_id' => null])->save();
            $this->context->clear();

            return $this->noActiveCompany();
        }

        $this->context->set($company);

        return $next($request);
    }

    private function noActiveCompany(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'No active company selected.',
            'code' => 'no_active_company',
        ], 409);
    }
}
