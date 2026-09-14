<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Tenancy\CompanyContext;
use Illuminate\Validation\ValidationException;
use Modules\Companies\Models\Role;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\WarehouseService;

it('lists warehouses for the active company only, default first', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(fn () => Warehouse::factory()->for($company)->create(['name' => 'Secondary']));
    withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create(['name' => 'Main']));
    [$foreign] = companyWithOwner();
    withoutTenantScope(fn () => Warehouse::factory()->for($foreign)->create(['name' => 'Foreign only']));

    $this->actingAs($owner);

    $response = $this->getJson(apiUrl('warehouses'))->assertOk();

    $names = collect($response->json('data'))->pluck('name');
    expect($names->all())->toBe(['Main', 'Secondary'])
        ->and($names)->not->toContain('Foreign only');
});

it('creates the first warehouse as the default automatically, ignoring client input', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);

    $this->postJson(apiUrl('warehouses'), ['name' => 'Main', 'is_default' => false])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Main')
        ->assertJsonPath('data.is_default', true);

    expect(Warehouse::where('company_id', $company->id)->where('is_default', true)->count())->toBe(1);
});

it('does not default a second warehouse unless explicitly requested', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);
    $this->postJson(apiUrl('warehouses'), ['name' => 'Main'])->assertCreated();

    $this->postJson(apiUrl('warehouses'), ['name' => 'Secondary'])
        ->assertCreated()
        ->assertJsonPath('data.is_default', false);

    expect(Warehouse::where('company_id', $company->id)->where('is_default', true)->pluck('name')->all())
        ->toBe(['Main']);
});

it('switches the default when a new warehouse explicitly requests it', function () {
    [$company, $owner] = companyWithOwner();
    $this->actingAs($owner);
    $this->postJson(apiUrl('warehouses'), ['name' => 'Main'])->assertCreated();

    $this->postJson(apiUrl('warehouses'), ['name' => 'Secondary', 'is_default' => true])
        ->assertCreated()
        ->assertJsonPath('data.is_default', true);

    expect(Warehouse::where('company_id', $company->id)->where('is_default', true)->pluck('name')->all())
        ->toBe(['Secondary']);
});

it('requires a unique name per company but allows reuse across companies', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create(['name' => 'Main']));
    $this->actingAs($owner);

    $this->postJson(apiUrl('warehouses'), ['name' => 'Main'])
        ->assertStatus(422)->assertJsonValidationErrorFor('name');

    [, $ownerB] = companyWithOwner();
    $this->actingAs($ownerB);
    $this->postJson(apiUrl('warehouses'), ['name' => 'Main'])->assertCreated();
});

it('updates a warehouse and switches the default via update', function () {
    [$company, $owner] = companyWithOwner();
    $main = withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create(['name' => 'Main']));
    $secondary = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create(['name' => 'Secondary']));
    $this->actingAs($owner);

    $this->putJson(apiUrl("warehouses/{$secondary->id}"), ['location' => 'North dock'])
        ->assertOk()->assertJsonPath('data.location', 'North dock');

    $this->putJson(apiUrl("warehouses/{$secondary->id}"), ['is_default' => true])
        ->assertOk()->assertJsonPath('data.is_default', true);

    expect($main->fresh()->is_default)->toBeFalse()
        ->and($secondary->fresh()->is_default)->toBeTrue();
});

it('refuses to unset the only default warehouse', function () {
    [$company, $owner] = companyWithOwner();
    $main = withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create());
    $this->actingAs($owner);

    $this->putJson(apiUrl("warehouses/{$main->id}"), ['is_default' => false])
        ->assertStatus(422)->assertJsonValidationErrorFor('is_default');

    expect($main->fresh()->is_default)->toBeTrue();
});

it('deletes a non-default warehouse', function () {
    [$company, $owner] = companyWithOwner();
    withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create());
    $secondary = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    $this->actingAs($owner);

    $this->deleteJson(apiUrl("warehouses/{$secondary->id}"))->assertNoContent();

    expect(Warehouse::find($secondary->id))->toBeNull();
});

it('refuses to delete the default warehouse while others exist', function () {
    [$company, $owner] = companyWithOwner();
    $main = withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create());
    withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());
    $this->actingAs($owner);

    $this->deleteJson(apiUrl("warehouses/{$main->id}"))
        ->assertStatus(422)->assertJsonValidationErrorFor('is_default');

    expect(Warehouse::find($main->id))->not->toBeNull();
});

it('allows deleting the default warehouse when it is the only one', function () {
    [$company, $owner] = companyWithOwner();
    $main = withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create());
    $this->actingAs($owner);

    $this->deleteJson(apiUrl("warehouses/{$main->id}"))->assertNoContent();

    expect(Warehouse::where('company_id', $company->id)->count())->toBe(0);
});

it('flips the default from the current DB state, not a stale pre-lock copy of the model', function () {
    // Regression for a race where $warehouse was bound (e.g. by route-model
    // binding) before a concurrent request already flipped the default
    // underneath it — the service must re-derive state after locking, not
    // trust the instance it was handed.
    [$company] = companyWithOwner();
    $a = withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create(['name' => 'A']));
    $b = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create(['name' => 'B']));

    app(CompanyContext::class)->set($company);
    $staleA = $a->fresh(); // is_default: true, about to go stale below

    Warehouse::whereKey($a->id)->update(['is_default' => false]);
    Warehouse::whereKey($b->id)->update(['is_default' => true]);

    app(WarehouseService::class)->update($staleA, ['is_default' => true]);

    expect($a->fresh()->is_default)->toBeTrue()
        ->and($b->fresh()->is_default)->toBeFalse();
});

it('refuses to delete a warehouse that became the actual default after it was loaded, despite a stale non-default flag', function () {
    [$company] = companyWithOwner();
    $a = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create(['name' => 'A']));
    $b = withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create(['name' => 'B']));

    app(CompanyContext::class)->set($company);
    $staleA = $a->fresh(); // is_default: false, about to go stale below

    Warehouse::whereKey($b->id)->update(['is_default' => false]);
    Warehouse::whereKey($a->id)->update(['is_default' => true]);

    expect(fn () => app(WarehouseService::class)->delete($staleA))
        ->toThrow(ValidationException::class);

    expect(Warehouse::find($a->id))->not->toBeNull()
        ->and($a->fresh()->is_default)->toBeTrue();
});

it('enforces per-action warehouse permissions', function () {
    [$company, $owner] = companyWithOwner();
    $warehouse = withoutTenantScope(fn () => Warehouse::factory()->for($company)->default()->create());
    $purchasing = User::factory()->create();
    addMember($company, $purchasing, Role::PURCHASING_MANAGER);
    $purchasing->forceFill(['current_company_id' => $company->id])->save();
    $this->actingAs($purchasing->refresh());

    // purchasing-manager only has warehouse.view
    $this->getJson(apiUrl('warehouses'))->assertOk();
    $this->postJson(apiUrl('warehouses'), ['name' => 'X'])->assertStatus(403);
    $this->putJson(apiUrl("warehouses/{$warehouse->id}"), ['name' => 'X'])->assertStatus(403);
    $this->deleteJson(apiUrl("warehouses/{$warehouse->id}"))->assertStatus(403);
});

it('forbids warehouse access without any warehouse permission', function () {
    [$company] = companyWithOwner();
    $rep = User::factory()->create();
    addMember($company, $rep, Role::SALES_REP);
    $rep->forceFill(['current_company_id' => $company->id])->save();
    $this->actingAs($rep->refresh());

    $this->getJson(apiUrl('warehouses'))->assertStatus(403);
});
