<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryLedger;
use Modules\Products\Models\Product;

/**
 * @return array{0: Modules\Companies\Models\Company, 1: App\Models\User, 2: Product, 3: Warehouse}
 */
function ledgerFixture(): array
{
    [$company, $owner] = companyWithOwner();
    $product = withoutTenantScope(fn () => Product::factory()->for($company)->create());
    $warehouse = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    actingInCompany($owner, $company);

    return [$company, $owner, $product, $warehouse];
}

it('records a purchase and creates the stock projection at exactly the signed quantity', function () {
    [, , $product, $warehouse] = ledgerFixture();

    $movement = app(InventoryLedger::class)->record(
        $product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000', '5.5000',
    );

    expect($movement->quantity)->toBe('10.0000')
        ->and($movement->unit_cost)->toBe('5.5000')
        ->and($movement->type)->toBe('purchase');

    $stock = Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
    expect($stock->quantity)->toBe('10.0000');
});

it('accumulates a mixed sequence of signed movements into the projection correctly', function () {
    [, , $product, $warehouse] = ledgerFixture();
    $ledger = app(InventoryLedger::class);

    $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '100.0000');
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_SALE, '-30.0000');
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_ADJUSTMENT, '5.0000');

    $stock = Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
    expect($stock->quantity)->toBe('75.0000');
    expect(InventoryMovement::where('product_id', $product->id)->count())->toBe(3);
});

it('keeps the stock projection equal to SUM(movements.quantity) — the projection/ledger property', function () {
    [, , $product, $warehouse] = ledgerFixture();
    $ledger = app(InventoryLedger::class);

    $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '50.0000');
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_SALE, '-12.5000');
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_RETURN, '2.5000');
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_ADJUSTMENT, '-1.0000');

    $sum = InventoryMovement::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->sum('quantity');
    $stockQuantity = Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity');

    expect($stockQuantity)->toBe(bcadd((string) $sum, '0', 4));
});

it('blocks a movement that would take stock below zero by default', function () {
    [, , $product, $warehouse] = ledgerFixture();
    $ledger = app(InventoryLedger::class);
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');

    expect(fn () => $ledger->record($product, $warehouse, InventoryMovement::TYPE_SALE, '-15.0000'))
        ->toThrow(ValidationException::class);

    $stock = Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
    expect($stock->quantity)->toBe('10.0000');
    expect(InventoryMovement::where('product_id', $product->id)->count())->toBe(1);
});

it('rolls back the stock row creation and the movement together when the first movement on a pair fails', function () {
    [, , $product, $warehouse] = ledgerFixture();

    expect(fn () => app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_SALE, '-5.0000'))
        ->toThrow(ValidationException::class);

    expect(Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->exists())->toBeFalse();
    expect(InventoryMovement::where('product_id', $product->id)->count())->toBe(0);
});

it('allows a forced adjustment to take stock negative as a true-up correction', function () {
    [, , $product, $warehouse] = ledgerFixture();
    $ledger = app(InventoryLedger::class);
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');

    $movement = $ledger->record($product, $warehouse, InventoryMovement::TYPE_ADJUSTMENT, '-15.0000', force: true);

    expect($movement->quantity)->toBe('-15.0000');
    expect(Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'))
        ->toBe('-5.0000');
});

it('allows force on damage the same as adjustment', function () {
    [, , $product, $warehouse] = ledgerFixture();
    $ledger = app(InventoryLedger::class);
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '5.0000');

    $movement = $ledger->record($product, $warehouse, InventoryMovement::TYPE_DAMAGE, '-8.0000', force: true);

    expect($movement->quantity)->toBe('-8.0000');
});

it('never honors force for a non-adjustment/damage type — transfers never fabricate stock', function () {
    [, , $product, $warehouse] = ledgerFixture();
    $ledger = app(InventoryLedger::class);
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');

    expect(fn () => $ledger->record(
        $product, $warehouse, InventoryMovement::TYPE_TRANSFER_OUT, '-15.0000', force: true,
    ))->toThrow(ValidationException::class);

    expect(Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'))
        ->toBe('10.0000');
});

it('rejects an unknown movement type', function () {
    [, , $product, $warehouse] = ledgerFixture();

    expect(fn () => app(InventoryLedger::class)->record($product, $warehouse, 'bogus', '1.0000'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a quantity sign that contradicts the movement type, so `type` stays semantically meaningful', function () {
    [, , $product, $warehouse] = ledgerFixture();
    $ledger = app(InventoryLedger::class);
    $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');

    // A "purchase" that decreases stock, or a "sale" that increases it,
    // would corrupt COGS/valuation reporting keyed off type — even though
    // the stock arithmetic itself would stay internally consistent.
    expect(fn () => $ledger->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '-5.0000'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $ledger->record($product, $warehouse, InventoryMovement::TYPE_SALE, '5.0000'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $ledger->record($product, $warehouse, InventoryMovement::TYPE_RETURN, '-1.0000'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $ledger->record($product, $warehouse, InventoryMovement::TYPE_TRANSFER_IN, '-1.0000'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $ledger->record($product, $warehouse, InventoryMovement::TYPE_TRANSFER_OUT, '1.0000'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $ledger->record($product, $warehouse, InventoryMovement::TYPE_DAMAGE, '1.0000'))
        ->toThrow(InvalidArgumentException::class);

    // Zero satisfies neither "positive" nor "negative" for a directional type.
    expect(fn () => $ledger->record($product, $warehouse, InventoryMovement::TYPE_SALE, '0.0000'))
        ->toThrow(InvalidArgumentException::class);

    // adjustment has no fixed direction — a true-up correction may go either way.
    $up = $ledger->record($product, $warehouse, InventoryMovement::TYPE_ADJUSTMENT, '2.0000');
    $down = $ledger->record($product, $warehouse, InventoryMovement::TYPE_ADJUSTMENT, '-3.0000');
    expect($up->quantity)->toBe('2.0000')->and($down->quantity)->toBe('-3.0000');
});

it('keeps the ledger and projection isolated per warehouse for the same product', function () {
    [$company, , $product] = ledgerFixture();
    $warehouseA = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    $warehouseB = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    $ledger = app(InventoryLedger::class);

    $ledger->record($product, $warehouseA, InventoryMovement::TYPE_PURCHASE, '10.0000');
    $ledger->record($product, $warehouseB, InventoryMovement::TYPE_PURCHASE, '20.0000');

    expect(Stock::where('warehouse_id', $warehouseA->id)->value('quantity'))->toBe('10.0000');
    expect(Stock::where('warehouse_id', $warehouseB->id)->value('quantity'))->toBe('20.0000');
});

it('records created_by from the authenticated user', function () {
    [, $owner, $product, $warehouse] = ledgerFixture();

    $movement = app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '1.0000');

    expect($movement->created_by)->toBe($owner->id);
});

it('stores unit_cost and reference fields when provided, and leaves them null when omitted', function () {
    [, , $product, $warehouse] = ledgerFixture();
    $ledger = app(InventoryLedger::class);

    $withRef = $ledger->record(
        $product, $warehouse, InventoryMovement::TYPE_PURCHASE, '3.0000',
        unitCost: '9.9900', referenceType: 'purchase_order', referenceId: 42, note: 'PO-1',
    );
    $withoutRef = $ledger->record($product, $warehouse, InventoryMovement::TYPE_ADJUSTMENT, '1.0000');

    expect($withRef->unit_cost)->toBe('9.9900')
        ->and($withRef->reference_type)->toBe('purchase_order')
        ->and($withRef->reference_id)->toBe(42)
        ->and($withRef->note)->toBe('PO-1');
    expect($withoutRef->unit_cost)->toBeNull()
        ->and($withoutRef->reference_type)->toBeNull();
});
