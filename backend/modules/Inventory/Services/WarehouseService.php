<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Support\Tenancy\CompanyContext;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Warehouse;
use RuntimeException;

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
        $companyId = $this->context->id();

        return $this->serialized($companyId, function () use ($companyId, $data): Warehouse {
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
        $companyId = $this->context->id();

        return $this->serialized($companyId, function () use ($companyId, $warehouse, $data): Warehouse {
            $current = $this->requireLocked($this->lockCompanyWarehouses($companyId)->get(), $warehouse);

            if (array_key_exists('is_default', $data)) {
                $wantsDefault = (bool) $data['is_default'];

                if (! $wantsDefault && $current->is_default) {
                    throw ValidationException::withMessages([
                        'is_default' => 'A company must always have a default warehouse — set another one as default first.',
                    ]);
                }

                if ($wantsDefault && ! $current->is_default) {
                    $this->clearCurrentDefault($companyId);
                    $current->is_default = true;
                }
            }

            $current->fill(array_intersect_key($data, array_flip(['name', 'location', 'status'])));
            $current->save();

            return $current;
        });
    }

    public function delete(Warehouse $warehouse): void
    {
        $companyId = $this->context->id();

        $this->serialized($companyId, function () use ($companyId, $warehouse): void {
            $locked = $this->lockCompanyWarehouses($companyId)->get();
            $current = $this->requireLocked($locked, $warehouse);

            $othersExist = $locked->contains(fn (Warehouse $other): bool => $other->isNot($current));

            if ($current->is_default && $othersExist) {
                throw ValidationException::withMessages([
                    'is_default' => 'Set another warehouse as default before deleting this one.',
                ]);
            }

            $current->delete();
        });
    }

    /**
     * Pulls $warehouse's freshly-locked, current copy out of an
     * already-locked company warehouse set — never the possibly-stale
     * instance bound before the transaction/lock started.
     *
     * @param  Collection<int, Warehouse>  $locked
     */
    private function requireLocked(Collection $locked, Warehouse $warehouse): Warehouse
    {
        $current = $locked->firstWhere($warehouse->getKeyName(), $warehouse->getKey());

        if ($current === null) {
            throw (new ModelNotFoundException)->setModel(Warehouse::class, [$warehouse->getKey()]);
        }

        return $current;
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

    /**
     * Runs $callback in a transaction, serialized against every other
     * warehouse mutation for the same company via a MySQL named lock.
     *
     * A `lockForUpdate()` row lock can't carry this on its own: the "is this
     * the company's first warehouse" check in create() locks a range that
     * may match zero rows, and InnoDB only takes a gap lock over an empty
     * range under REPEATABLE READ — under READ COMMITTED it takes no lock at
     * all, silently breaking the "exactly one default warehouse" invariant.
     * Pinning the isolation level instead (`SET TRANSACTION ISOLATION
     * LEVEL`) isn't an option either: MySQL rejects it once a transaction is
     * already open, which it always is under the test suite's
     * `RefreshDatabase` wrapping transaction. A named lock sidesteps both
     * problems — it holds regardless of isolation level or ambient
     * transaction state.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function serialized(int $companyId, Closure $callback): mixed
    {
        $lockName = "warehouse-default:{$companyId}";

        $acquired = DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName])?->acquired;

        if (! $acquired) {
            throw new RuntimeException("Could not acquire the warehouse lock for company {$companyId}.");
        }

        try {
            return DB::transaction($callback);
        } finally {
            DB::statement('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }
}
