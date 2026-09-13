<?php

declare(strict_types=1);

use Modules\Products\Models\Product;
use Modules\Products\Models\ProductCategory;

/**
 * The mandatory cross-tenant isolation suite (CLAUDE.md / ADR-0006) for the
 * Products module. A user active in company A must never read or mutate
 * company B's products or categories — proof the middleware-priority fix
 * (docs/PHASE-2-PLAN.md §7.6) tenant-scopes implicit route-model binding.
 */
beforeEach(function () {
    [$this->companyA, $this->alice] = companyWithOwner();
    [$this->companyB, $this->bob] = companyWithOwner();

    $this->foreignProduct = withoutTenantScope(
        fn () => Product::factory()->for($this->companyB)->create(['sku' => 'B-ONLY'])
    );
    $this->foreignCategory = withoutTenantScope(
        fn () => ProductCategory::factory()->for($this->companyB)->create(['name' => 'B Category'])
    );
});

it('404s reading a foreign product', function () {
    $this->actingAs($this->alice);

    $this->getJson(apiUrl("products/{$this->foreignProduct->id}"))->assertNotFound();
});

it('404s updating a foreign product', function () {
    $this->actingAs($this->alice);

    $this->putJson(apiUrl("products/{$this->foreignProduct->id}"), ['name' => 'Hijacked'])->assertNotFound();

    expect($this->foreignProduct->fresh()->name)->not->toBe('Hijacked');
});

it('404s deleting a foreign product', function () {
    $this->actingAs($this->alice);

    $this->deleteJson(apiUrl("products/{$this->foreignProduct->id}"))->assertNotFound();

    expect(withoutTenantScope(fn () => Product::find($this->foreignProduct->id)))->not->toBeNull();
});

it('never lists a foreign company\'s products', function () {
    withoutTenantScope(fn () => Product::factory()->for($this->companyA)->create(['sku' => 'A-ONLY']));
    $this->actingAs($this->alice);

    $skus = collect($this->getJson(apiUrl('products'))->assertOk()->json('data'))->pluck('sku');

    expect($skus)->toContain('A-ONLY')->not->toContain('B-ONLY');
});

it('404s reading, updating and deleting a foreign category', function () {
    $this->actingAs($this->alice);

    $this->getJson(apiUrl("categories/{$this->foreignCategory->id}"))->assertNotFound();
    $this->putJson(apiUrl("categories/{$this->foreignCategory->id}"), ['name' => 'Hijacked'])->assertNotFound();
    $this->deleteJson(apiUrl("categories/{$this->foreignCategory->id}"))->assertNotFound();

    $category = withoutTenantScope(fn () => ProductCategory::find($this->foreignCategory->id));
    expect($category?->name)->toBe('B Category');
});

it('cannot assign a product to a foreign category', function () {
    $this->actingAs($this->alice);

    $this->postJson(apiUrl('products'), [
        'sku' => 'X', 'name' => 'X', 'category_id' => $this->foreignCategory->id,
    ])->assertStatus(422)->assertJsonValidationErrorFor('category_id');
});
