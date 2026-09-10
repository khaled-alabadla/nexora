<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Modules\Identity\Http\Requests\ForgotPasswordRequest;

final class PasswordResetLinkController
{
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        // Always report success — never disclose whether the address is registered.
        return ApiResponse::make(
            null,
            'If that email is registered, a reset link is on its way.',
        );
    }
}
