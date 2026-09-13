<?php

declare(strict_types=1);

use Modules\Products\Models\Product;
use Modules\Products\Models\ProductCategory;

it('exposes the category tree and product relations', function () {
    [$company] = companyWithOwner();

    withoutTenantScope(function () use ($company): void {
        $parent = ProductCategory::factory()->for($company)->create();
        $child = ProductCategory::factory()->for($company)->create(['parent_id' => $parent->id]);
        $product = Product::factory()->for($company)->create(['category_id' => $child->id]);

        expect($child->parent->is($parent))->toBeTrue()
            ->and($parent->children->pluck('id'))->toContain($child->id)
            ->and($child->products->pluck('id'))->toContain($product->id)
            ->and($product->isActive())->toBeTrue();

        $product->update(['status' => Product::STATUS_INACTIVE]);
        expect($product->isActive())->toBeFalse();
    });
});
