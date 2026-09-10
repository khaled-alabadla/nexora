<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Identity\Actions\RegisterUser;
use Modules\Identity\Http\Requests\RegisterRequest;
use Modules\Identity\Support\SessionPayload;

final class RegisteredUserController
{
    public function store(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        /** @var array{name: string, email: string, password: string, company_name: string} $data */
        $data = $request->validated();

        $user = $action->handle($data);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return ApiResponse::created(
            SessionPayload::for($user),
            'Registration successful.',
        );
    }
}
