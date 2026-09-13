<?php

declare(strict_types=1);

use App\Support\Authorization\Permissions;
use Illuminate\Support\Facades\Route;
use Modules\Products\Http\Controllers\ProductCategoryController;
use Modules\Products\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Products module API routes
|--------------------------------------------------------------------------
|
| Loaded by ProductsServiceProvider under config('nexora.api_prefix') with the
| "api" middleware group. Every route here operates inside the active company
| (Products is a tenant-owned resource — see ADR-0006).
|
*/

Route::middleware(['auth:sanctum', 'active-company'])->group(function (): void {
    Route::get('categories', [ProductCategoryController::class, 'index'])
        ->middleware('permission:'.Permissions::CATEGORY_MANAGE)
        ->name('categories.index');
    Route::post('categories', [ProductCategoryController::class, 'store'])
        ->middleware('permission:'.Permissions::CATEGORY_MANAGE)
        ->name('categories.store');
    Route::get('categories/{category}', [ProductCategoryController::class, 'show'])
        ->middleware('permission:'.Permissions::CATEGORY_MANAGE)
        ->name('categories.show');
    Route::put('categories/{category}', [ProductCategoryController::class, 'update'])
        ->middleware('permission:'.Permissions::CATEGORY_MANAGE)
        ->name('categories.update');
    Route::delete('categories/{category}', [ProductCategoryController::class, 'destroy'])
        ->middleware('permission:'.Permissions::CATEGORY_MANAGE)
        ->name('categories.destroy');

    Route::get('products', [ProductController::class, 'index'])
        ->middleware('permission:'.Permissions::PRODUCT_VIEW)
        ->name('products.index');
    Route::post('products', [ProductController::class, 'store'])
        ->middleware('permission:'.Permissions::PRODUCT_CREATE)
        ->name('products.store');
    Route::get('products/{product}', [ProductController::class, 'show'])
        ->middleware('permission:'.Permissions::PRODUCT_VIEW)
        ->name('products.show');
    Route::put('products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:'.Permissions::PRODUCT_UPDATE)
        ->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:'.Permissions::PRODUCT_DELETE)
        ->name('products.destroy');
});
