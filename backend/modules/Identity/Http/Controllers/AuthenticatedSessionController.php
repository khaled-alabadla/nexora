<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\Identity\Http\Requests\LoginRequest;
use Modules\Identity\Support\SessionPayload;

final class AuthenticatedSessionController
{
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

        return ApiResponse::make(
            SessionPayload::for($user),
            'Signed in.',
        );
    }

    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::noContent();
    }
}
