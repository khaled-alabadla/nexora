<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accounting module API routes
|--------------------------------------------------------------------------
|
| Loaded by AccountingServiceProvider under the config('nexora.api_prefix')
| prefix with the "api" middleware group.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    //
});
