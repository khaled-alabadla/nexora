<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Inventory\Http\Requests\StoreWarehouseRequest;
use Modules\Inventory\Http\Requests\UpdateWarehouseRequest;
use Modules\Inventory\Http\Resources\WarehouseResource;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\WarehouseService;

/**
 * Warehouses for the active company. Not paginated — a small reference list,
 * like Categories. All writes go through WarehouseService, which is the only
 * place the one-default-warehouse invariant is enforced.
 */
final class WarehouseController
{
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::query()->orderByDesc('is_default')->orderBy('name')->get();

        return ApiResponse::make(WarehouseResource::collection($warehouses));
    }

    public function store(StoreWarehouseRequest $request, WarehouseService $warehouses): JsonResponse
    {
        $warehouse = $warehouses->create($request->validated());

        return ApiResponse::created(new WarehouseResource($warehouse), 'Warehouse created.');
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        return ApiResponse::make(new WarehouseResource($warehouse));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse, WarehouseService $warehouses): JsonResponse
    {
        $updated = $warehouses->update($warehouse, $request->validated());

        return ApiResponse::make(new WarehouseResource($updated), 'Warehouse updated.');
    }

    public function destroy(Warehouse $warehouse, WarehouseService $warehouses): Response
    {
        $warehouses->delete($warehouse);

        return ApiResponse::noContent();
    }
}
