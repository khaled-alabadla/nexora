<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Closure;
use Modules\Companies\Models\Company;

/**
 * Holds the active company for the current request / job (see ADR-0006).
 *
 * Registered as a singleton. Bound by the SetActiveCompany middleware for
 * tenant-scoped HTTP routes; set explicitly (or bypassed) in queued jobs.
 */
final class CompanyContext
{
    private ?Company $company = null;

    private bool $scopingDisabled = false;

    public function set(Company $company): void
    {
        $this->company = $company;
    }

    public function clear(): void
    {
        $this->company = null;
    }

    public function has(): bool
    {
        return $this->company !== null;
    }

    public function company(): Company
    {
        return $this->company ?? throw TenantContextMissingException::make();
    }

    public function id(): int
    {
        return $this->company()->getKey();
    }

    /**
     * True while a withoutScope() closure is executing — the global scope
     * checks this to skip tenant filtering for system-level work.
     */
    public function scopingDisabled(): bool
    {
        return $this->scopingDisabled;
    }

    /**
     * Run $callback with tenant scoping disabled (seeders, platform jobs).
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function withoutScope(Closure $callback): mixed
    {
        $previous = $this->scopingDisabled;
        $this->scopingDisabled = true;

        try {
            return $callback();
        } finally {
            $this->scopingDisabled = $previous;
        }
    }
}
