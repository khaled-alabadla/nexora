<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\AuthenticatedSessionController;
use Modules\Identity\Http\Controllers\EmailVerificationNotificationController;
use Modules\Identity\Http\Controllers\NewPasswordController;
use Modules\Identity\Http\Controllers\PasswordResetLinkController;
use Modules\Identity\Http\Controllers\RegisteredUserController;
use Modules\Identity\Http\Controllers\SessionUserController;
use Modules\Identity\Http\Controllers\VerifyEmailController;

/*
|--------------------------------------------------------------------------
| Identity module API routes
|--------------------------------------------------------------------------
|
| Loaded by IdentityServiceProvider under config('nexora.api_prefix') with the
| "api" middleware group. Auth transport is the Sanctum SPA cookie session
| (ADR-0004): the "web" guard is active for stateful first-party requests.
|
*/

Route::prefix('auth')->group(function (): void {
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('auth.register');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->name('auth.login');

    Route::post('password/forgot', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('auth.password.forgot');

    Route::post('password/reset', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('auth.password.reset');

    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', SessionUserController::class)->name('auth.me');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('auth.logout');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});
