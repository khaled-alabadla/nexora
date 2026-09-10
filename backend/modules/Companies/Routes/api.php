<?php

declare(strict_types=1);

use App\Support\Authorization\Permissions;
use Illuminate\Support\Facades\Route;
use Modules\Companies\Http\Controllers\ActiveCompanyController;
use Modules\Companies\Http\Controllers\CompanyController;
use Modules\Companies\Http\Controllers\CurrentCompanyController;
use Modules\Companies\Http\Controllers\InvitationAcceptanceController;
use Modules\Companies\Http\Controllers\InvitationController;
use Modules\Companies\Http\Controllers\MemberController;

/*
|--------------------------------------------------------------------------
| Companies module API routes
|--------------------------------------------------------------------------
|
| Loaded by CompaniesServiceProvider under config('nexora.api_prefix') with the
| "api" middleware group. Tenancy: routes that operate *inside* a company sit
| behind the "active-company" middleware (binds CompanyContext); routes a user
| needs *before* choosing a company do not.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    // No active company required.
    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('companies', [CompanyController::class, 'store'])
        ->middleware('verified')
        ->name('companies.store');
    Route::put('companies/{company}/active', [ActiveCompanyController::class, 'update'])
        ->whereNumber('company')
        ->name('companies.active');
    Route::post('invitations/{token}/accept', [InvitationAcceptanceController::class, 'store'])
        ->name('invitations.accept');

    // Operates inside the active company.
    Route::middleware('active-company')->group(function (): void {
        Route::get('company', [CurrentCompanyController::class, 'show'])->name('company.show');
        Route::put('company', [CurrentCompanyController::class, 'update'])
            ->middleware('permission:'.Permissions::COMPANY_UPDATE)
            ->name('company.update');

        Route::get('company/members', [MemberController::class, 'index'])
            ->middleware('permission:'.Permissions::MEMBER_VIEW)
            ->name('company.members.index');
        Route::patch('company/members/{user}', [MemberController::class, 'update'])
            ->whereNumber('user')
            ->middleware('permission:'.Permissions::MEMBER_ROLE_UPDATE)
            ->name('company.members.update');
        Route::delete('company/members/{user}', [MemberController::class, 'destroy'])
            ->whereNumber('user')
            ->middleware('permission:'.Permissions::MEMBER_REMOVE)
            ->name('company.members.destroy');

        Route::get('company/invitations', [InvitationController::class, 'index'])
            ->middleware('permission:'.Permissions::MEMBER_VIEW)
            ->name('company.invitations.index');
        Route::post('company/invitations', [InvitationController::class, 'store'])
            ->middleware('permission:'.Permissions::MEMBER_INVITE)
            ->name('company.invitations.store');
        Route::delete('company/invitations/{invitation}', [InvitationController::class, 'destroy'])
            ->whereNumber('invitation')
            ->middleware('permission:'.Permissions::MEMBER_INVITE)
            ->name('company.invitations.destroy');
    });
});
