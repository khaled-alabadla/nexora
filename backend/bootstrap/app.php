<?php

declare(strict_types=1);

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // First-party SPA authentication (Sanctum cookie session) — see ADR-0004.
        $middleware->statefulApi();

        // API consumers are always treated as JSON clients.
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render API failures as JSON regardless of the client's Accept header.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is(config('nexora.api_prefix').'/*') || $request->expectsJson()
        );
    })->create();
