<?php

declare(strict_types=1);

use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryLedger;
use Modules\Products\Models\Product;

/**
 * The mandatory cross-tenant isolation suite (CLAUDE.md / ADR-0006) for
 * inventory — proof that stock/movement reads never leak across companies,
 * and that InventoryLedger itself refuses to mix a company_id with a
 * foreign product/warehouse even if a future caller got tenant-scoping
 * wrong upstream.
 */
beforeEach(function () {
    [$this->companyA, $this->alice] = companyWithOwner();
    [$this->companyB, $this->bob] = companyWithOwner();

    $this->productB = withoutTenantScope(fn () => Product::factory()->for($this->companyB)->create());
    $this->warehouseB = withoutTenantScope(fn () => Warehouse::factory()->for($this->companyB)->create());

    actingInCompany($this->bob, $this->companyB);
    app(InventoryLedger::class)->record($this->productB, $this->warehouseB, InventoryMovement::TYPE_PURCHASE, '99.0000');
});

it('never lists a foreign company\'s stock', function () {
    actingInCompany($this->alice, $this->companyA);

    $response = $this->getJson(apiUrl('inventory/stock'))->assertOk();

    expect($response->json('data'))->toHaveCount(0);
});

it('never lists a foreign company\'s movements', function () {
    actingInCompany($this->alice, $this->companyA);

    $response = $this->getJson(apiUrl('inventory/movements'))->assertOk();

    expect($response->json('data'))->toHaveCount(0);
});

it('cannot use ?product_id/?warehouse_id to reach into a foreign company\'s stock', function () {
    actingInCompany($this->alice, $this->companyA);

    $response = $this->getJson(apiUrl("inventory/stock?product_id={$this->productB->id}&warehouse_id={$this->warehouseB->id}"))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(0);
});

it('InventoryLedger refuses to record a movement mixing the active company with a foreign product', function () {
    $productA = withoutTenantScope(fn () => Product::factory()->for($this->companyA)->create());
    actingInCompany($this->alice, $this->companyA);

    expect(fn () => app(InventoryLedger::class)->record(
        $productA, $this->warehouseB, InventoryMovement::TYPE_PURCHASE, '1.0000',
    ))->toThrow(InvalidArgumentException::class);
});

it('InventoryLedger refuses to record a movement mixing the active company with a foreign warehouse', function () {
    $warehouseA = withoutTenantScope(fn () => Warehouse::factory()->for($this->companyA)->create());
    actingInCompany($this->alice, $this->companyA);

    expect(fn () => app(InventoryLedger::class)->record(
        $this->productB, $warehouseA, InventoryMovement::TYPE_PURCHASE, '1.0000',
    ))->toThrow(InvalidArgumentException::class);
});
