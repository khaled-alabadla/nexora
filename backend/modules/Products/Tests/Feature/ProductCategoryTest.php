<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Companies\Models\Role;
use Modules\Products\Models\ProductCategory;

it('lists categories for the active company only', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(fn () => ProductCategory::factory()->for($company)->count(2)->create());
    [$foreign] = companyWithOwner();
    withoutTenantScope(fn () => ProductCategory::factory()->for($foreign)->create(['name' => 'Foreign only']));

    $this->actingAs($owner);

    $response = $this->getJson(apiUrl('categories'))->assertOk();

    expect($response->json('data'))->toHaveCount(2)
        ->and(collect($response->json('data'))->pluck('name'))->not->toContain('Foreign only');
});

it('creates a category', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('categories'), ['name' => 'Electronics'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Electronics')
        ->assertJsonPath('data.status', 'active');

    expect(ProductCategory::where('company_id', $company->id)->where('name', 'Electronics')->exists())->toBeTrue();
});

it('creates a nested category under a parent in the same company', function () {
    [, $owner] = companyWithOwner();
    $this->actingAs($owner);
    $parentId = $this->postJson(apiUrl('categories'), ['name' => 'Electronics'])->json('data.id');

    $this->postJson(apiUrl('categories'), ['name' => 'Phones', 'parent_id' => $parentId])
        ->assertCreated()
        ->assertJsonPath('data.parent_id', $parentId);
});

it('rejects a duplicate category name within the company', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(fn () => ProductCategory::factory()->for($company)->create(['name' => 'Electronics']));
    $this->actingAs($owner);

    $this->postJson(apiUrl('categories'), ['name' => 'Electronics'])
        ->assertStatus(422)->assertJsonValidationErrorFor('name');
});

it('allows the same category name in a different company', function () {
    [$companyA] = companyWithOwner();
    withoutTenantScope(fn () => ProductCategory::factory()->for($companyA)->create(['name' => 'Electronics']));
    [, $ownerB] = companyWithOwner();
    $this->actingAs($ownerB);

    $this->postJson(apiUrl('categories'), ['name' => 'Electronics'])->assertCreated();
});

it('rejects a parent_id belonging to another company', function () {
    [$foreign] = companyWithOwner();
    $foreignCategory = withoutTenantScope(fn () => ProductCategory::factory()->for($foreign)->create());
    [, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('categories'), ['name' => 'X', 'parent_id' => $foreignCategory->id])
        ->assertStatus(422)->assertJsonValidationErrorFor('parent_id');
});

it('rejects a category being set as its own parent', function () {
    [$company, $owner] = companyWithOwner();
    $category = withoutTenantScope(fn () => ProductCategory::factory()->for($company)->create());
    $this->actingAs($owner);

    $this->putJson(apiUrl("categories/{$category->id}"), ['parent_id' => $category->id])
        ->assertStatus(422)->assertJsonValidationErrorFor('parent_id');
});

it('rejects a multi-level cycle, not just a direct self-parent', function () {
    [$company, $owner] = companyWithOwner();
    $a = withoutTenantScope(fn () => ProductCategory::factory()->for($company)->create());
    $b = withoutTenantScope(fn () => ProductCategory::factory()->for($company)->create(['parent_id' => $a->id]));
    $this->actingAs($owner);

    // A -> B already exists; making A a child of B would create a cycle A -> B -> A.
    $this->putJson(apiUrl("categories/{$a->id}"), ['parent_id' => $b->id])
        ->assertStatus(422)->assertJsonValidationErrorFor('parent_id');

    expect($a->fresh()->parent_id)->toBeNull();
});

it('updates and deletes a category, orphaning its children and products', function () {
    [$company, $owner] = companyWithOwner();
    $parent = withoutTenantScope(fn () => ProductCategory::factory()->for($company)->create());
    $child = withoutTenantScope(fn () => ProductCategory::factory()->for($company)->create(['parent_id' => $parent->id]));
    $this->actingAs($owner);

    $this->putJson(apiUrl("categories/{$parent->id}"), ['name' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.name', 'Renamed');

    $this->deleteJson(apiUrl("categories/{$parent->id}"))->assertNoContent();

    expect(ProductCategory::find($parent->id))->toBeNull()
        ->and($child->fresh()->parent_id)->toBeNull();
});

it('forbids category management without the permission', function () {
    [$company, $owner] = companyWithOwner();
    $rep = User::factory()->create();
    addMember($company, $rep, Role::SALES_REP);
    $rep->forceFill(['current_company_id' => $company->id])->save();
    $this->actingAs($rep->refresh());

    $this->getJson(apiUrl('categories'))->assertStatus(403);
    $this->postJson(apiUrl('categories'), ['name' => 'X'])->assertStatus(403);
});

it('allows category management for the inventory manager', function () {
    [$company] = companyWithOwner();
    $manager = User::factory()->create();
    addMember($company, $manager, Role::INVENTORY_MANAGER);
    $manager->forceFill(['current_company_id' => $company->id])->save();
    $this->actingAs($manager->refresh());

    $this->postJson(apiUrl('categories'), ['name' => 'Electronics'])->assertCreated();
});
