<?php

declare(strict_types=1);

use App\Support\Authorization\Permissions;
use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\AdjustmentController;
use Modules\Inventory\Http\Controllers\InventoryController;
use Modules\Inventory\Http\Controllers\WarehouseController;

/*
|--------------------------------------------------------------------------
| Inventory module API routes
|--------------------------------------------------------------------------
|
| Loaded by InventoryServiceProvider under config('nexora.api_prefix') with
| the "api" middleware group. Tenant-owned — every route sits behind
| "active-company" (ADR-0006).
|
*/

Route::middleware(['auth:sanctum', 'active-company'])->group(function (): void {
    Route::get('warehouses', [WarehouseController::class, 'index'])
        ->middleware('permission:'.Permissions::WAREHOUSE_VIEW)
        ->name('warehouses.index');
    Route::post('warehouses', [WarehouseController::class, 'store'])
        ->middleware('permission:'.Permissions::WAREHOUSE_CREATE)
        ->name('warehouses.store');
    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])
        ->middleware('permission:'.Permissions::WAREHOUSE_VIEW)
        ->name('warehouses.show');
    Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])
        ->middleware('permission:'.Permissions::WAREHOUSE_UPDATE)
        ->name('warehouses.update');
    Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])
        ->middleware('permission:'.Permissions::WAREHOUSE_DELETE)
        ->name('warehouses.destroy');

    Route::get('inventory/stock', [InventoryController::class, 'stock'])
        ->middleware('permission:'.Permissions::INVENTORY_VIEW)
        ->name('inventory.stock');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])
        ->middleware('permission:'.Permissions::INVENTORY_VIEW)
        ->name('inventory.movements');
    Route::post('inventory/adjustments', [AdjustmentController::class, 'store'])
        ->middleware('permission:'.Permissions::INVENTORY_ADJUST)
        ->name('inventory.adjustments.store');
});
