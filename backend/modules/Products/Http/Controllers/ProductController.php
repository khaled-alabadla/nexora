<?php

declare(strict_types=1);

namespace Modules\Products\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Support\Http\QueryFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Products\Http\Requests\StoreProductRequest;
use Modules\Products\Http\Requests\UpdateProductRequest;
use Modules\Products\Http\Resources\ProductResource;
use Modules\Products\Models\Product;

final class ProductController
{
    public function index(QueryFilter $filter): JsonResponse
    {
        $query = $filter->apply(
            Product::query()->with('category'),
            filterable: ['status', 'category_id'],
            searchable: ['sku', 'name', 'barcode'],
            sortable: ['name', 'sku', 'created_at'],
            defaultSort: 'name',
        );

        return ApiResponse::paginated($query->paginate($filter->perPage())->through(
            fn (Product $product): ProductResource => new ProductResource($product)
        ));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());

        return ApiResponse::created(new ProductResource($product->load('category')), 'Product created.');
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::make(new ProductResource($product->load('category')));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return ApiResponse::make(new ProductResource($product->load('category')), 'Product updated.');
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return ApiResponse::noContent();
    }
}
