<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Companies\Models\Role;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryLedger;
use Modules\Products\Models\Product;

it('lists current stock levels for the active company, paginated', function () {
    [$company, $owner] = companyWithOwner();
    $product = withoutTenantScope(fn () => Product::factory()->for($company)->create(['name' => 'Widget']));
    $warehouse = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    actingInCompany($owner, $company);
    app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');

    $response = $this->getJson(apiUrl('inventory/stock'))->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.quantity'))->toBe('10.0000');
    expect($response->json('data.0.product.sku'))->toBe($product->sku);
    expect($response->json('meta.total'))->toBe(1);
});

it('filters stock by warehouse_id and product_id', function () {
    [$company, $owner] = companyWithOwner();
    $productA = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    $productB = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    $warehouseA = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    $warehouseB = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    actingInCompany($owner, $company);
    $ledger = app(InventoryLedger::class);
    $ledger->record($productA, $warehouseA, InventoryMovement::TYPE_PURCHASE, '1.0000');
    $ledger->record($productB, $warehouseA, InventoryMovement::TYPE_PURCHASE, '2.0000');
    $ledger->record($productA, $warehouseB, InventoryMovement::TYPE_PURCHASE, '3.0000');

    $byWarehouse = $this->getJson(apiUrl("inventory/stock?warehouse_id={$warehouseA->id}"))->assertOk();
    expect($byWarehouse->json('data'))->toHaveCount(2);

    $byProduct = $this->getJson(apiUrl("inventory/stock?product_id={$productA->id}"))->assertOk();
    expect($byProduct->json('data'))->toHaveCount(2);

    $byBoth = $this->getJson(apiUrl("inventory/stock?product_id={$productA->id}&warehouse_id={$warehouseA->id}"))->assertOk();
    expect($byBoth->json('data'))->toHaveCount(1);
});

it('lists the movement ledger, filterable by type, newest first by default', function () {
    [$company, $owner] = companyWithOwner();
    $product = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    $warehouse = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    actingInCompany($owner, $company);
    $ledger = app(InventoryLedger::class);
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_SALE, '-4.0000');

    $all = $this->getJson(apiUrl('inventory/movements'))->assertOk();
    expect($all->json('data'))->toHaveCount(2);
    expect($all->json('data.0.type'))->toBe('sale'); // newest first

    $onlySales = $this->getJson(apiUrl('inventory/movements?type=sale'))->assertOk();
    expect($onlySales->json('data'))->toHaveCount(1)
        ->and($onlySales->json('data.0.quantity'))->toBe('-4.0000');
});

it('forbids stock and movement access without inventory.view', function () {
    [$company] = companyWithOwner();
    $rep = User::factory()->create();
    addMember($company, $rep, Role::SALES_REP);
    $rep->forceFill(['current_company_id' => $company->id])->save();
    $this->actingAs($rep->refresh());

    $this->getJson(apiUrl('inventory/stock'))->assertStatus(403);
    $this->getJson(apiUrl('inventory/movements'))->assertStatus(403);
});

it('allows inventory.view-only roles to read stock and movements', function () {
    [$company] = companyWithOwner();
    $accountant = User::factory()->create();
    addMember($company, $accountant, Role::ACCOUNTANT);
    $accountant->forceFill(['current_company_id' => $company->id])->save();
    $this->actingAs($accountant->refresh());

    $this->getJson(apiUrl('inventory/stock'))->assertOk();
    $this->getJson(apiUrl('inventory/movements'))->assertOk();
});
