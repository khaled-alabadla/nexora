<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Nexora's backend is an API. The SPA is served separately (see the
| `frontend/` package). Only a couple of non-API concerns live here:
| Sanctum's CSRF-cookie route (registered by the package) and the health
| endpoint at /up (framework default).
|
*/

Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url(config('nexora.api_prefix')),
]));
