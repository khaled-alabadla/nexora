<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Companies\Models\Role;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryLedger;
use Modules\Products\Models\Product;

/**
 * @return array{0: Modules\Companies\Models\Company, 1: App\Models\User, 2: Product, 3: Warehouse}
 */
function adjustmentFixture(): array
{
    [$company, $owner] = companyWithOwner();
    $product = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    $warehouse = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    actingInCompany($owner, $company);
    app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '20.0000');

    return [$company, $owner, $product, $warehouse];
}

it('records a positive adjustment', function () {
    [, , $product, $warehouse] = adjustmentFixture();

    $response = $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'adjustment',
        'lines' => [
            ['product_id' => $product->id, 'quantity_delta' => '5.0000', 'reason' => 'Recount'],
        ],
    ])->assertCreated();

    expect($response->json('data.0.type'))->toBe('adjustment')
        ->and($response->json('data.0.quantity'))->toBe('5.0000')
        ->and($response->json('data.0.note'))->toBe('Recount');

    expect(Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'))
        ->toBe('25.0000');
});

it('records a damage adjustment with a negative delta', function () {
    [, , $product, $warehouse] = adjustmentFixture();

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'damage',
        'lines' => [
            ['product_id' => $product->id, 'quantity_delta' => '-3.0000', 'reason' => 'Broken in transit'],
        ],
    ])->assertCreated()->assertJsonPath('data.0.quantity', '-3.0000');

    expect(Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'))
        ->toBe('17.0000');
});

it('rejects a positive delta for damage', function () {
    [, , $product, $warehouse] = adjustmentFixture();

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'damage',
        'lines' => [
            ['product_id' => $product->id, 'quantity_delta' => '3.0000'],
        ],
    ])->assertStatus(422)->assertJsonValidationErrorFor('lines.0.quantity_delta');
});

it('rejects a zero delta', function () {
    [, , $product, $warehouse] = adjustmentFixture();

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'adjustment',
        'lines' => [
            ['product_id' => $product->id, 'quantity_delta' => '0'],
        ],
    ])->assertStatus(422)->assertJsonValidationErrorFor('lines.0.quantity_delta');
});

it('blocks an adjustment that would take stock negative without force', function () {
    [, , $product, $warehouse] = adjustmentFixture();

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'adjustment',
        'lines' => [
            ['product_id' => $product->id, 'quantity_delta' => '-50.0000'],
        ],
    ])->assertStatus(422)->assertJsonValidationErrorFor('quantity');

    expect(Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'))
        ->toBe('20.0000');
});

it('allows a forced adjustment to take stock negative as a true-up', function () {
    [, , $product, $warehouse] = adjustmentFixture();

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'adjustment',
        'force' => true,
        'lines' => [
            ['product_id' => $product->id, 'quantity_delta' => '-50.0000'],
        ],
    ])->assertCreated();

    expect(Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'))
        ->toBe('-30.0000');
});

it('records multiple lines atomically — one line failing rolls back the whole adjustment', function () {
    [$company, $owner, $product, $warehouse] = adjustmentFixture();
    $productB = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    app(InventoryLedger::class)->record($productB, $warehouse, InventoryMovement::TYPE_PURCHASE, '2.0000');

    $movementCountBefore = InventoryMovement::count();

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'adjustment',
        'lines' => [
            ['product_id' => $product->id, 'quantity_delta' => '5.0000'], // would succeed alone
            ['product_id' => $productB->id, 'quantity_delta' => '-10.0000'], // would go negative, rejected
        ],
    ])->assertStatus(422);

    expect(InventoryMovement::count())->toBe($movementCountBefore);
    expect(Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'))
        ->toBe('20.0000');
});

it('records unit_cost and reason on each line', function () {
    [, , $product, $warehouse] = adjustmentFixture();

    $response = $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'adjustment',
        'lines' => [
            ['product_id' => $product->id, 'quantity_delta' => '1.0000', 'unit_cost' => '4.5000', 'reason' => 'Found extra unit'],
        ],
    ])->assertCreated();

    expect($response->json('data.0.unit_cost'))->toBe('4.5000')
        ->and($response->json('data.0.note'))->toBe('Found extra unit');
});

it('enforces the inventory.adjust permission', function () {
    [$company, , $product, $warehouse] = adjustmentFixture();
    $rep = User::factory()->create();
    addMember($company, $rep, Role::SALES_REP);
    $rep->forceFill(['current_company_id' => $company->id])->save();
    $this->actingAs($rep->refresh());

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'adjustment',
        'lines' => [['product_id' => $product->id, 'quantity_delta' => '1.0000']],
    ])->assertStatus(403);
});

it('rejects a warehouse_id or product_id belonging to another company', function () {
    [, , $product, $warehouse] = adjustmentFixture();
    [$companyB, $bob] = companyWithOwner();
    $foreignProduct = withoutTenantScope(fn () => Product::factory()->for($companyB)->create());
    $foreignWarehouse = withoutTenantScope(fn () => Warehouse::factory()->for($companyB)->create());

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $foreignWarehouse->id,
        'type' => 'adjustment',
        'lines' => [['product_id' => $product->id, 'quantity_delta' => '1.0000']],
    ])->assertStatus(422)->assertJsonValidationErrorFor('warehouse_id');

    $this->postJson(apiUrl('inventory/adjustments'), [
        'warehouse_id' => $warehouse->id,
        'type' => 'adjustment',
        'lines' => [['product_id' => $foreignProduct->id, 'quantity_delta' => '1.0000']],
    ])->assertStatus(422)->assertJsonValidationErrorFor('lines.0.product_id');

    unset($bob);
});
