<?php

declare(strict_types=1);

use Modules\Inventory\Models\Warehouse;

/**
 * The mandatory cross-tenant isolation suite (CLAUDE.md / ADR-0006) for
 * warehouses — proof that route-model binding (fixed in Phase 2.1, §7.6)
 * keeps a foreign company's warehouse unreachable.
 */
beforeEach(function () {
    [$this->companyA, $this->alice] = companyWithOwner();
    [$this->companyB, $this->bob] = companyWithOwner();

    $this->foreignWarehouse = withoutTenantScope(
        fn () => Warehouse::factory()->for($this->companyB)->default()->create(['name' => 'B Warehouse'])
    );
});

it('404s reading a foreign warehouse', function () {
    $this->actingAs($this->alice);

    $this->getJson(apiUrl("warehouses/{$this->foreignWarehouse->id}"))->assertNotFound();
});

it('404s updating a foreign warehouse', function () {
    $this->actingAs($this->alice);

    $this->putJson(apiUrl("warehouses/{$this->foreignWarehouse->id}"), ['name' => 'Hijacked'])->assertNotFound();

    expect($this->foreignWarehouse->fresh()->name)->toBe('B Warehouse');
});

it('404s deleting a foreign warehouse', function () {
    $this->actingAs($this->alice);

    $this->deleteJson(apiUrl("warehouses/{$this->foreignWarehouse->id}"))->assertNotFound();

    expect(withoutTenantScope(fn () => Warehouse::find($this->foreignWarehouse->id)))->not->toBeNull();
});

it('never lists a foreign company\'s warehouses', function () {
    withoutTenantScope(fn () => Warehouse::factory()->for($this->companyA)->default()->create(['name' => 'A Warehouse']));
    $this->actingAs($this->alice);

    $names = collect($this->getJson(apiUrl('warehouses'))->assertOk()->json('data'))->pluck('name');

    expect($names)->toContain('A Warehouse')->not->toContain('B Warehouse');
});

it('a foreign warehouse never influences this company\'s default invariant', function () {
    // B already has a default warehouse; creating A's first warehouse must
    // still become A's own default — the invariant is per-company.
    $this->actingAs($this->alice);

    $this->postJson(apiUrl('warehouses'), ['name' => 'A Warehouse'])
        ->assertCreated()
        ->assertJsonPath('data.is_default', true);
});
