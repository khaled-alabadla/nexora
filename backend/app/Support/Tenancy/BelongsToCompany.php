<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;
use Modules\Companies\Models\Company;

/**
 * Applied to every tenant-owned model (see ADR-0006).
 *
 * - global scope: WHERE company_id = <active company>
 * - creating hook: company_id is forced from the context, never from input
 * - `company_id` must NOT be in $fillable
 *
 * @property int $company_id
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new class implements Scope
        {
            public function apply(Builder $builder, Model $model): void
            {
                $context = app(CompanyContext::class);

                if ($context->scopingDisabled()) {
                    return;
                }

                $builder->where(
                    $model->qualifyColumn('company_id'),
                    $context->id(),
                );
            }
        });

        static::creating(function (Model $model): void {
            $context = app(CompanyContext::class);

            if ($context->scopingDisabled()) {
                return;
            }

            $model->setAttribute('company_id', $context->id());
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Run a query/operation on this model with tenant scoping disabled.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function withoutCompanyScope(Closure $callback): mixed
    {
        return app(CompanyContext::class)->withoutScope($callback);
    }
}
