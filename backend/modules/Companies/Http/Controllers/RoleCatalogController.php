<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Companies\Http\Resources\RoleResource;
use Modules\Companies\Models\Role;

/**
 * The system role catalogue (Phase 1 has no per-company custom roles). Used by
 * the SPA to populate role pickers.
 */
final class RoleCatalogController
{
    public function __invoke(): JsonResponse
    {
        $roles = Role::query()->orderByDesc('level')->get();

        return ApiResponse::make(RoleResource::collection($roles));
    }
}
