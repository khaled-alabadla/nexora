<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

/**
 * Builds the API's canonical success envelope (see docs/API.md):
 *
 *   { "data": <payload>, "message": "..." }
 *
 * Collection/pagination envelopes ({ data, message, meta }) are added when the
 * first list endpoint needs them (Phase 1+), so this stays minimal for now.
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

    private static function normalize(mixed $data): mixed
    {
        return match (true) {
            $data instanceof JsonResource => $data->resolve(),
            $data instanceof Arrayable => $data->toArray(),
            default => $data,
        };
    }
}
