<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use RuntimeException;

/**
 * Thrown when tenant-scoped code runs without an active company bound.
 *
 * A missing context is always a bug (a route missing SetActiveCompany, or a job
 * that forgot to set/bypass the context) — never a reason to fall back to an
 * unscoped query.
 */
final class TenantContextMissingException extends RuntimeException
{
    public static function make(): self
    {
        return new self('No active company is bound to the current context.');
    }
}
