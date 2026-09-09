<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core API routes
|--------------------------------------------------------------------------
|
| Served under the prefix in config('nexora.api_prefix') (default /api/v1).
| Business endpoints live in their own module route files
| (modules/<Name>/Routes/api.php) and are loaded by each module provider.
|
*/

Route::get('health', HealthController::class)->name('health');
