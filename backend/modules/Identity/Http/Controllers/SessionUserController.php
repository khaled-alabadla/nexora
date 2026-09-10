<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Identity\Support\SessionPayload;

final class SessionUserController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::make(SessionPayload::for($user));
    }
}
