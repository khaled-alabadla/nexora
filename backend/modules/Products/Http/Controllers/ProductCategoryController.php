<?php

declare(strict_types=1);

namespace Modules\Products\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Products\Http\Requests\StoreProductCategoryRequest;
use Modules\Products\Http\Requests\UpdateProductCategoryRequest;
use Modules\Products\Http\Resources\ProductCategoryResource;
use Modules\Products\Models\ProductCategory;

/**
 * Categories for the active company. A small, flat-ish list — not paginated.
 */
final class ProductCategoryController
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::query()->orderBy('name')->get();

        return ApiResponse::make(ProductCategoryResource::collection($categories));
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $category = ProductCategory::query()->create($request->validated());

        return ApiResponse::created(new ProductCategoryResource($category), 'Category created.');
    }

    public function show(ProductCategory $category): JsonResponse
    {
        return ApiResponse::make(new ProductCategoryResource($category));
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category): JsonResponse
    {
        $category->update($request->validated());

        return ApiResponse::make(new ProductCategoryResource($category), 'Category updated.');
    }

    public function destroy(ProductCategory $category): Response
    {
        // Child categories and products keep their FK, nulled (see migrations).
        $category->delete();

        return ApiResponse::noContent();
    }
}
