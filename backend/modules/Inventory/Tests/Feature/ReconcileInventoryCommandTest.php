<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryLedger;
use Modules\Products\Models\Product;

/**
 * @return array{0: Modules\Companies\Models\Company, 1: App\Models\User, 2: Product, 3: Warehouse}
 */
function reconcileFixture(): array
{
    [$company, $owner] = companyWithOwner();
    $product = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    $warehouse = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    actingInCompany($owner, $company);

    return [$company, $owner, $product, $warehouse];
}

/**
 * Corrupts a stock row directly (bypassing InventoryLedger) to simulate
 * drift — a bad manual DB edit, a bug in an older version of the service,
 * or a restored-from-backup mismatch.
 */
function corruptStock(int $productId, int $warehouseId, string $quantity): void
{
    DB::table('stock')
        ->where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->update(['quantity' => $quantity]);
}

it('reports zero drift on a freshly reconciled ledger', function () {
    [, , $product, $warehouse] = reconcileFixture();
    app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');

    $this->artisan('inventory:reconcile')
        ->expectsOutputToContain('Repaired 0/1 stock row(s) with drift.')
        ->assertSuccessful();
});

it('detects and repairs a corrupted stock row without touching the ledger', function () {
    [, , $product, $warehouse] = reconcileFixture();
    app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');
    corruptStock($product->id, $warehouse->id, '999.0000');

    expect(Stock::where('product_id', $product->id)->value('quantity'))->toBe('999.0000');
    $ledgerCountBefore = InventoryMovement::count();

    $this->artisan('inventory:reconcile')
        ->expectsOutputToContain('Repaired 1/1 stock row(s) with drift.')
        ->assertSuccessful();

    expect(Stock::where('product_id', $product->id)->value('quantity'))->toBe('10.0000');
    expect(InventoryMovement::count())->toBe($ledgerCountBefore);
});

it('creates a missing stock row from ledger history', function () {
    [, , $product, $warehouse] = reconcileFixture();
    app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '25.0000');
    Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->delete();

    expect(Stock::where('product_id', $product->id)->exists())->toBeFalse();

    $this->artisan('inventory:reconcile')->assertSuccessful();

    expect(Stock::where('product_id', $product->id)->value('quantity'))->toBe('25.0000');
});

it('zeroes out a stock row that has no ledger history at all', function () {
    [$company, , $product, $warehouse] = reconcileFixture();
    // A stock row with no movements behind it (e.g. hand-inserted) — drift
    // in the opposite direction from the usual case.
    withoutTenantScope(function () use ($company, $product, $warehouse) {
        $stock = new Stock;
        $stock->company_id = $company->id;
        $stock->product_id = $product->id;
        $stock->warehouse_id = $warehouse->id;
        $stock->quantity = '50.0000';
        $stock->save();
    });

    $this->artisan('inventory:reconcile')->assertSuccessful();

    expect(Stock::where('product_id', $product->id)->value('quantity'))->toBe('0.0000');
});

it('--dry-run reports drift without writing any change', function () {
    [, , $product, $warehouse] = reconcileFixture();
    app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');
    corruptStock($product->id, $warehouse->id, '999.0000');

    $this->artisan('inventory:reconcile --dry-run')
        ->expectsOutputToContain('Found 1/1 stock row(s) with drift.')
        ->assertSuccessful();

    expect(Stock::where('product_id', $product->id)->value('quantity'))->toBe('999.0000');
});

it('--company scopes reconciliation to a single tenant, leaving other companies\' drift untouched', function () {
    [, , $productA, $warehouseA] = reconcileFixture();
    app(InventoryLedger::class)->record($productA, $warehouseA, InventoryMovement::TYPE_PURCHASE, '10.0000');
    corruptStock($productA->id, $warehouseA->id, '111.0000');

    [$companyB, , $productB, $warehouseB] = reconcileFixture();
    app(InventoryLedger::class)->record($productB, $warehouseB, InventoryMovement::TYPE_PURCHASE, '20.0000');
    corruptStock($productB->id, $warehouseB->id, '222.0000');

    $this->artisan("inventory:reconcile --company={$companyB->id}")->assertSuccessful();

    // CompanyContext is currently bound to B (the last reconcileFixture()
    // call) — read both companies' rows unscoped so B's context doesn't mask
    // A's row out of the query entirely.
    expect(withoutTenantScope(fn () => Stock::where('product_id', $productB->id)->value('quantity')))->toBe('20.0000');
    expect(withoutTenantScope(fn () => Stock::where('product_id', $productA->id)->value('quantity')))->toBe('111.0000');
});
