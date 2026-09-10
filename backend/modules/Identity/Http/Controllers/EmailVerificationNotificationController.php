<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmailVerificationNotificationController
{
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::make(null, 'Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return ApiResponse::make(null, 'Verification link sent.');
    }
}
