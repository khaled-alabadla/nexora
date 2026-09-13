<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

/**
 * Builds the API's canonical success envelope (see docs/API.md):
 *
 *   { "data": <payload>, "message": "..." }
 *
 * and the paginated variant:
 *
 *   { "data": [...], "message": "...", "meta": { current_page, last_page, … } }
 */
final class ApiResponse
{
    /**
     * @param  array<string, string>  $headers
     */
    public static function make(mixed $data, string $message = 'OK', int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'data' => self::normalize($data),
            'message' => $message,
        ], $status, $headers);
    }

    public static function created(mixed $data, string $message = 'Created'): JsonResponse
    {
        return self::make($data, $message, 201);
    }

    /**
     * A 204 with no body — for deletes and other no-content successes.
     */
    public static function noContent(): Response
    {
        return new Response('', 204);
    }

    /**
     * Envelope a paginator's page as `{data, message, meta}` (docs/API.md §3/§5).
     *
     * Each item is normalized the same way `make()` normalizes a single payload,
     * so callers can pass either a raw Eloquent paginator or one already mapped
     * through resources: `Product::paginate()->through(fn ($p) => new ProductResource($p))`.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return new JsonResponse([
            'data' => array_map(self::normalize(...), $paginator->items()),
            'message' => $message,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    private static function normalize(mixed $data): mixed
    {
        return match (true) {
            $data instanceof JsonResource => $data->resolve(),
            $data instanceof Arrayable => $data->toArray(),
            default => $data,
        };
    }
}
