<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sales module API routes
|--------------------------------------------------------------------------
|
| Loaded by SalesServiceProvider under the config('nexora.api_prefix')
| prefix with the "api" middleware group.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    //
});
