<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Companies\Models\Role;
use Modules\Products\Models\Product;
use Modules\Products\Models\ProductCategory;

it('creates a product', function () {
    [$company, $owner] = companyWithOwner();
    $category = withoutTenantScope(fn () => ProductCategory::factory()->for($company)->create());
    $this->actingAs($owner);

    $this->postJson(apiUrl('products'), [
        'category_id' => $category->id,
        'sku' => 'SKU-001',
        'name' => 'Widget',
        'cost_price' => 10.5,
        'selling_price' => 19.99,
    ])->assertCreated()
        ->assertJsonPath('data.sku', 'SKU-001')
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.status', 'active');

    expect(Product::where('company_id', $company->id)->where('sku', 'SKU-001')->exists())->toBeTrue();
});

it('requires sku and name', function () {
    [, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('products'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('sku')
        ->assertJsonValidationErrorFor('name');
});

it('rejects a duplicate sku within the company but allows it in another company', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(fn () => Product::factory()->for($company)->create(['sku' => 'DUP']));
    $this->actingAs($owner);

    $this->postJson(apiUrl('products'), ['sku' => 'DUP', 'name' => 'X'])
        ->assertStatus(422)->assertJsonValidationErrorFor('sku');

    [, $ownerB] = companyWithOwner();
    $this->actingAs($ownerB);
    $this->postJson(apiUrl('products'), ['sku' => 'DUP', 'name' => 'X'])->assertCreated();
});

it('rejects a duplicate barcode within the company', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(fn () => Product::factory()->for($company)->create(['barcode' => '012345']));
    $this->actingAs($owner);

    $this->postJson(apiUrl('products'), ['sku' => 'NEW', 'name' => 'X', 'barcode' => '012345'])
        ->assertStatus(422)->assertJsonValidationErrorFor('barcode');
});

it('allows multiple products with no barcode', function () {
    [, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('products'), ['sku' => 'A', 'name' => 'X'])->assertCreated();
    $this->postJson(apiUrl('products'), ['sku' => 'B', 'name' => 'Y'])->assertCreated();
});

it('rejects negative prices and a category from another company', function () {
    [$foreignCompany] = companyWithOwner();
    $foreignCategory = withoutTenantScope(fn () => ProductCategory::factory()->for($foreignCompany)->create());
    [, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('products'), [
        'sku' => 'X', 'name' => 'X', 'cost_price' => -5, 'category_id' => $foreignCategory->id,
    ])->assertStatus(422)
        ->assertJsonValidationErrorFor('cost_price')
        ->assertJsonValidationErrorFor('category_id');
});

it('lists products with pagination, filtering, search and sort', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(function () use ($company): void {
        Product::factory()->for($company)->create(['name' => 'Alpha Widget', 'sku' => 'A1', 'status' => Product::STATUS_ACTIVE]);
        Product::factory()->for($company)->create(['name' => 'Beta Gadget', 'sku' => 'B1', 'status' => Product::STATUS_INACTIVE]);
        Product::factory()->for($company)->create(['name' => 'Gamma Widget', 'sku' => 'C1', 'status' => Product::STATUS_ACTIVE]);
    });
    $this->actingAs($owner);

    $page = $this->getJson(apiUrl('products?per_page=2&sort=name'))->assertOk();
    expect($page->json('meta.total'))->toBe(3)
        ->and($page->json('meta.per_page'))->toBe(2)
        ->and($page->json('data.0.name'))->toBe('Alpha Widget');

    $active = $this->getJson(apiUrl('products?status=active'))->assertOk();
    expect(collect($active->json('data'))->pluck('name')->all())->toBe(['Alpha Widget', 'Gamma Widget']);

    $search = $this->getJson(apiUrl('products?search=widget'))->assertOk();
    expect($search->json('meta.total'))->toBe(2);
});

it('escapes LIKE wildcards in the search term instead of matching them literally', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(function () use ($company): void {
        Product::factory()->for($company)->create(['sku' => 'A_1', 'name' => 'Underscore SKU']);
        Product::factory()->for($company)->create(['sku' => 'AX1', 'name' => 'No underscore']);
    });
    $this->actingAs($owner);

    // A raw LIKE would treat "_" as "match any one character" and hit AX1 too.
    $response = $this->getJson(apiUrl('products?search='.urlencode('A_1')))->assertOk();

    expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['A_1']);
});

it('updates a product', function () {
    [$company, $owner] = companyWithOwner();
    $product = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    $this->actingAs($owner);

    $this->putJson(apiUrl("products/{$product->id}"), ['name' => 'Renamed', 'selling_price' => 42])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.selling_price', '42.0000');
});

it('soft deletes a product and reserves its sku', function () {
    [$company, $owner] = companyWithOwner();
    $product = withoutTenantScope(fn () => Product::factory()->for($company)->create(['sku' => 'GONE']));
    $this->actingAs($owner);

    $this->deleteJson(apiUrl("products/{$product->id}"))->assertNoContent();

    expect(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();

    $this->postJson(apiUrl('products'), ['sku' => 'GONE', 'name' => 'New'])
        ->assertStatus(422)->assertJsonValidationErrorFor('sku');
});

it('enforces per-action product permissions', function () {
    [$company, $owner] = companyWithOwner();
    $product = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    $rep = User::factory()->create();
    addMember($company, $rep, Role::SALES_REP);
    $rep->forceFill(['current_company_id' => $company->id])->save();
    $this->actingAs($rep->refresh());

    // sales-rep only has product.view
    $this->getJson(apiUrl('products'))->assertOk();
    $this->postJson(apiUrl('products'), ['sku' => 'X', 'name' => 'X'])->assertStatus(403);
    $this->putJson(apiUrl("products/{$product->id}"), ['name' => 'X'])->assertStatus(403);
    $this->deleteJson(apiUrl("products/{$product->id}"))->assertStatus(403);
});
