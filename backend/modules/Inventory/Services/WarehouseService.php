<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Support\Tenancy\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Warehouse;

/**
 * Warehouse lifecycle for the active company. The single place that enforces
 * "exactly one default warehouse once the company has at least one" — the
 * database can't (MySQL has no partial unique index), so every write goes
 * through here, never a raw create()/update() on the model, and every
 * mutation locks the company's warehouse rows to serialize concurrent
 * default-flips (docs/PHASE-2-PLAN.md §7.9).
 */
final class WarehouseService
{
    public function __construct(private readonly CompanyContext $context) {}

    /**
     * @param  array<string, mixed>  $data  validated: name, location?, status?, is_default?
     */
    public function create(array $data): Warehouse
    {
        return DB::transaction(function () use ($data): Warehouse {
            $companyId = $this->context->id();

            $isFirst = ! $this->lockCompanyWarehouses($companyId)->exists();
            $wantsDefault = $isFirst || (bool) ($data['is_default'] ?? false);

            if ($wantsDefault && ! $isFirst) {
                $this->clearCurrentDefault($companyId);
            }

            $warehouse = new Warehouse;
            $warehouse->fill([
                'name' => $data['name'],
                'location' => $data['location'] ?? null,
                'status' => $data['status'] ?? Warehouse::STATUS_ACTIVE,
            ]);
            $warehouse->is_default = $wantsDefault;
            $warehouse->save();

            return $warehouse;
        });
    }

    /**
     * @param  array<string, mixed>  $data  validated: name?, location?, status?, is_default?
     */
    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data): Warehouse {
            $companyId = $this->context->id();
            $this->lockCompanyWarehouses($companyId)->get();

            if (array_key_exists('is_default', $data)) {
                $wantsDefault = (bool) $data['is_default'];

                if (! $wantsDefault && $warehouse->is_default) {
                    throw ValidationException::withMessages([
                        'is_default' => 'A company must always have a default warehouse — set another one as default first.',
                    ]);
                }

                if ($wantsDefault && ! $warehouse->is_default) {
                    $this->clearCurrentDefault($companyId);
                    $warehouse->is_default = true;
                }
            }

            $warehouse->fill(array_intersect_key($data, array_flip(['name', 'location', 'status'])));
            $warehouse->save();

            return $warehouse;
        });
    }

    public function delete(Warehouse $warehouse): void
    {
        DB::transaction(function () use ($warehouse): void {
            $companyId = $this->context->id();

            $othersExist = $this->lockCompanyWarehouses($companyId)
                ->whereKeyNot($warehouse->getKey())
                ->exists();

            if ($warehouse->is_default && $othersExist) {
                throw ValidationException::withMessages([
                    'is_default' => 'Set another warehouse as default before deleting this one.',
                ]);
            }

            $warehouse->delete();
        });
    }

    /**
     * @return Builder<Warehouse>
     */
    private function lockCompanyWarehouses(int $companyId): Builder
    {
        return Warehouse::query()->where('company_id', $companyId)->lockForUpdate();
    }

    private function clearCurrentDefault(int $companyId): void
    {
        Warehouse::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
