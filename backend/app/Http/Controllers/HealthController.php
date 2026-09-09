<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Liveness / readiness probe.
 *
 * Unauthenticated by design: it exposes only boolean up/down signals for the
 * datastore dependencies, never configuration or version detail.
 */
final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->probe(function (): bool {
            DB::connection()->getPdo();

            return true;
        });

        $cache = $this->probe(function (): bool {
            Cache::put('health:ping', 'pong', 5);

            return Cache::get('health:ping') === 'pong';
        });

        $healthy = $database && $cache;

        return ApiResponse::make(
            [
                'status' => $healthy ? 'ok' : 'degraded',
                'database' => $database,
                'cache' => $cache,
            ],
            $healthy ? 'OK' : 'Service degraded',
            $healthy ? 200 : 503,
        );
    }

    private function probe(callable $check): bool
    {
        try {
            return (bool) $check();
        } catch (Throwable) {
            return false;
        }
    }
}
